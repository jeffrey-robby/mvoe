<?php

namespace App\Services;

use App\Enums\StatutPresence;
use App\Models\Cohorte;
use App\Models\EvenementSync;
use App\Models\Facilitateur;
use App\Models\FicheFidelite;
use App\Models\ParentProgramme;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Sequence;
use App\Models\SequenceOuverte;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Réception de la remontée du kit facilitateur.
 *
 * C'est le SEUL chemin d'écriture des données de séance. Le client envoie des
 * événements horodatés et idempotents, jamais des états :
 *
 *   1. chaque événement est journalisé tel quel dans `evenements_sync` ;
 *   2. les tables métier en sont la projection courante.
 *
 * Un événement déjà reçu (même UUID) est ignoré en silence, sans erreur : le
 * kit peut renvoyer sa file autant de fois qu'il veut, y compris après une
 * coupure réseau au milieu d'un envoi. C'est ce qui rend la synchronisation
 * sûre sur un réseau qui tombe.
 *
 * Rien n'est jamais écrasé au sens fort : une correction met à jour la
 * projection, mais l'événement d'origine reste dans le journal.
 */
class ReceptionEvenements
{
    public const TYPES = ['seance', 'presence', 'sequence_ouverte', 'fiche_fidelite', 'bilan_seance'];

    /**
     * @param  array<int, array>  $evenements
     * @param  ?Carbon  $recuA  Horodatage serveur de réception. Surchargé
     *                          uniquement par les seeders et les tests, pour
     *                          rejouer des remontées anciennes.
     * @return array{recus:int, acceptes:array<int,string>, doublons:array<int,string>, rejetes:array<int,array>}
     */
    public function recevoir(array $evenements, Facilitateur $facilitateur, ?Carbon $recuA = null): array
    {
        $recuA ??= Carbon::now();

        $acceptes = [];
        $doublons = [];
        $rejetes = [];

        foreach ($this->ordonner($evenements) as $evenement) {
            $uuid = $evenement['uuid'] ?? null;

            if ($uuid === null || ! in_array($evenement['type'] ?? null, self::TYPES, true)) {
                $rejetes[] = ['uuid' => $uuid, 'raison' => 'evenement_malforme'];

                continue;
            }

            if (EvenementSync::where('uuid', $uuid)->exists()) {
                $doublons[] = $uuid;

                continue;
            }

            try {
                DB::transaction(function () use ($evenement, $facilitateur, $recuA) {
                    $this->projeter($evenement, $facilitateur, $recuA);
                    $this->journaliser($evenement, $recuA);
                });

                $acceptes[] = $uuid;
            } catch (Throwable $e) {
                $rejetes[] = ['uuid' => $uuid, 'raison' => $e->getMessage()];
            }
        }

        return [
            'recus' => count($evenements),
            'acceptes' => $acceptes,
            'doublons' => $doublons,
            'rejetes' => $rejetes,
        ];
    }

    /**
     * Les événements d'ouverture de séance passent d'abord : le reste s'y
     * rattache. Le kit peut donc envoyer sa file dans n'importe quel ordre.
     */
    private function ordonner(array $evenements): array
    {
        usort($evenements, fn (array $a, array $b) => (($a['type'] ?? '') === 'seance' ? 0 : 1)
            <=> (($b['type'] ?? '') === 'seance' ? 0 : 1));

        return $evenements;
    }

    private function projeter(array $evenement, Facilitateur $facilitateur, Carbon $recuA): void
    {
        $charge = $evenement['charge'] ?? [];

        match ($evenement['type']) {
            'seance' => $this->seance($evenement, $charge, $facilitateur, $recuA),
            'presence' => $this->presence($evenement, $charge, $facilitateur),
            'sequence_ouverte' => $this->sequenceOuverte($evenement, $charge, $facilitateur),
            'fiche_fidelite' => $this->ficheFidelite($evenement, $charge, $facilitateur),
            'bilan_seance' => $this->bilanSeance($evenement, $charge, $facilitateur),
        };
    }

    private function seance(array $evenement, array $charge, Facilitateur $facilitateur, Carbon $recuA): void
    {
        $cohorte = Cohorte::findOrFail($charge['cohorte_id']);

        abort_unless($cohorte->facilitateur_id === $facilitateur->id, 403, 'cohorte_non_attribuee');

        Seance::create([
            // Pour une ouverture de séance, l'UUID de l'événement EST celui de
            // la séance : c'est lui que porteront tous les événements suivants.
            'uuid' => $evenement['uuid'],
            'cohorte_id' => $cohorte->id,
            'module_id' => $charge['module_id'],
            'date' => $charge['date'],
            'facilitateur_id' => $facilitateur->id,
            'recue_a' => $recuA,
        ]);

        // Une séance remontée est la seule preuve d'activité dont dispose le
        // registre du superviseur.
        $date = Carbon::parse($charge['date']);

        if ($facilitateur->derniere_activite === null || $facilitateur->derniere_activite->lt($date)) {
            $facilitateur->forceFill(['derniere_activite' => $date])->save();
        }
    }

    private function presence(array $evenement, array $charge, Facilitateur $facilitateur): void
    {
        $seance = $this->seanceDe($evenement, $facilitateur);

        $parent = ParentProgramme::where('cohorte_id', $seance->cohorte_id)
            ->where('code_parent', $charge['code_parent'])
            ->firstOrFail();

        // Un seul état courant par parent et par séance : une correction
        // remplace la projection, l'événement d'origine reste au journal.
        Presence::updateOrCreate(
            ['seance_id' => $seance->id, 'parent_id' => $parent->id],
            ['uuid' => $evenement['uuid'], 'statut' => StatutPresence::from($charge['statut'])],
        );
    }

    private function sequenceOuverte(array $evenement, array $charge, Facilitateur $facilitateur): void
    {
        $seance = $this->seanceDe($evenement, $facilitateur);

        // Aucune contrainte d'unicité : revenir sur une séquence pendant la
        // séance est un fait réel, on garde chaque ouverture.
        SequenceOuverte::create([
            'uuid' => $evenement['uuid'],
            'seance_id' => $seance->id,
            'sequence_id' => $this->sequenceDuModule($charge['sequence_id'], $seance)->id,
            'ouverte_a' => Carbon::parse($charge['ouverte_a']),
            'duree_reelle_secondes' => $charge['duree_reelle_secondes'] ?? null,
        ]);
    }

    private function ficheFidelite(array $evenement, array $charge, Facilitateur $facilitateur): void
    {
        $seance = $this->seanceDe($evenement, $facilitateur);

        FicheFidelite::updateOrCreate(
            [
                'seance_id' => $seance->id,
                'sequence_id' => $this->sequenceDuModule($charge['sequence_id'], $seance)->id,
            ],
            [
                'uuid' => $evenement['uuid'],
                'realisee_bool' => $charge['realisee_bool'],
                'note_qualite' => $charge['note_qualite'] ?? null,
                'commentaire' => $charge['commentaire'] ?? null,
            ],
        );
    }

    private function bilanSeance(array $evenement, array $charge, Facilitateur $facilitateur): void
    {
        $seance = $this->seanceDe($evenement, $facilitateur);

        // « Qu'est-ce qui a le moins bien marché ? » : vaut pour toute la
        // séance, d'où l'absence de séquence rattachée.
        FicheFidelite::updateOrCreate(
            ['seance_id' => $seance->id, 'sequence_id' => null],
            [
                'uuid' => $evenement['uuid'],
                'realisee_bool' => null,
                'note_qualite' => null,
                'commentaire' => $charge['commentaire'],
            ],
        );
    }

    private function seanceDe(array $evenement, Facilitateur $facilitateur): Seance
    {
        $seance = Seance::where('uuid', $evenement['seance_uuid'] ?? null)->firstOrFail();

        abort_unless($seance->facilitateur_id === $facilitateur->id, 403, 'seance_non_attribuee');

        return $seance;
    }

    /**
     * Une séquence ne peut être rattachée qu'à une séance de son propre module.
     * Sans ce contrôle, un client fautif pourrait fausser le calcul de l'écart.
     */
    private function sequenceDuModule(int $sequenceId, Seance $seance): Sequence
    {
        $sequence = Sequence::findOrFail($sequenceId);

        abort_unless($sequence->module_id === $seance->module_id, 422, 'sequence_hors_module');

        return $sequence;
    }

    private function journaliser(array $evenement, Carbon $recuA): void
    {
        EvenementSync::create([
            'uuid' => $evenement['uuid'],
            'type' => $evenement['type'],
            'seance_uuid' => $evenement['seance_uuid'] ?? $evenement['uuid'],
            'charge' => $evenement['charge'] ?? [],
            'emis_a' => Carbon::parse($evenement['emis_a']),
            'recu_a' => $recuA,
        ]);
    }
}
