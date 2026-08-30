<?php

namespace App\Services;

use App\Enums\DifficulteFonctionnelle;
use App\Enums\GraviteSignalement;
use App\Enums\StatutPresence;
use App\Enums\StatutSignalement;
use App\Enums\TypeActivite;
use App\Enums\TypeSignalement;
use App\Models\Activite;
use App\Models\Cohorte;
use App\Models\EvenementSync;
use App\Models\Facilitateur;
use App\Models\FicheFidelite;
use App\Models\Foyer;
use App\Models\GroupeSoutien;
use App\Models\ModuleFormation;
use App\Models\Langue;
use App\Models\ParentProgramme;
use App\Models\Presence;
use App\Models\ProgressionFormation;
use App\Models\Seance;
use App\Models\Sequence;
use App\Models\SequenceOuverte;
use App\Models\Signalement;
use App\Models\Visite;
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
    public const TYPES = ['seance', 'presence', 'sequence_ouverte', 'fiche_fidelite',
        'bilan_seance', 'inscription_parent',
        'activite', 'groupe_soutien', 'foyer', 'visite', 'signalement',
        'progression_formation'];

    /**
     * Les champs qui ne doivent JAMAIS rester au journal.
     *
     * Le journal conserve chaque charge utile telle quelle et pour toujours :
     * c'est ce qui fait sa valeur de preuve. Un code d'accès en clair y
     * resterait donc éternellement lisible, alors même que la table `parents`
     * ne le stocke que haché. On le remplace à l'écriture.
     */
    private const CHAMPS_SECRETS = ['code_acces'];

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
     * Ce dont le reste dépend passe d'abord, et le kit peut donc envoyer sa
     * file dans n'importe quel ordre :
     *
     *   - l'inscription d'un parent, parce qu'un parent inscrit en début de
     *     séance est pointé dans la foulée, et que sa présence ne peut pas se
     *     rattacher à un dossier qui n'existe pas encore ; de même pour un
     *     foyer visité dans la foulée de sa création, et pour un groupe de
     *     soutien dont on remonte aussitôt la première réunion ;
     *   - l'ouverture de séance et l'activité, auxquelles le reste se rattache
     *     — un signalement naît d'une activité.
     */
    private const PRIORITE = [
        // Ce dont le reste dépend.
        'inscription_parent' => 0,
        'foyer' => 0,
        'groupe_soutien' => 0,
        // Ce à quoi le reste se rattache.
        'seance' => 1,
        'activite' => 1,
    ];

    private function ordonner(array $evenements): array
    {
        $rang = fn (array $e) => self::PRIORITE[$e['type'] ?? ''] ?? 2;

        usort($evenements, fn (array $a, array $b) => $rang($a) <=> $rang($b));

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
            'inscription_parent' => $this->inscriptionParent($charge, $facilitateur),
            'activite' => $this->activite($evenement, $charge, $facilitateur, $recuA),
            'groupe_soutien' => $this->groupeSoutien($evenement, $charge, $facilitateur),
            'foyer' => $this->foyer($evenement, $charge, $facilitateur, $recuA),
            'visite' => $this->visite($evenement, $charge, $facilitateur, $recuA),
            'signalement' => $this->signalement($evenement, $charge, $facilitateur, $recuA),
            'progression_formation' => $this->progressionFormation($charge, $facilitateur),
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
    /**
     * Inscription d'un parent par son facilitateur.
     *
     * Le parent ne s'inscrit jamais seul depuis un écran public : c'est le
     * facilitateur qui crée le dossier et lui remet son code en main propre.
     * Le parent l'ACTIVE ensuite en se connectant. La nuance n'est pas
     * rhétorique : rien n'est jamais créé par un visiteur anonyme.
     *
     * L'inscription se fait hors ligne, en séance ou en visite à domicile.
     * C'est donc l'appareil qui tire le code à quatre chiffres, sans quoi le
     * facilitateur n'aurait rien à remettre avant d'avoir retrouvé du réseau.
     *
     * Toujours AUCUN nom : un code, une langue, une situation.
     */
    private function inscriptionParent(array $charge, Facilitateur $facilitateur): void
    {
        $cohorte = Cohorte::findOrFail($charge['cohorte_id']);

        abort_unless($cohorte->facilitateur_id === $facilitateur->id, 403, 'cohorte_non_attribuee');

        ParentProgramme::create([
            'cohorte_id' => $cohorte->id,
            'code_parent' => $charge['code_parent'],
            // Le cast `hashed` du modèle hache à l'écriture. Le code en clair
            // ne survit ni en base, ni au journal.
            'code_acces' => $charge['code_acces'],
            // Le code de langue est résolu en identifiant : le kit envoie
            // « bulu », jamais un numéro de ligne qu'il ne peut pas connaître
            // hors ligne.
            'langue_id' => Langue::where('code', $charge['langue_pref'] ?? $charge['langue'] ?? 'fr')
                ->value('id') ?? Langue::parDefaut()->id,
            'statut_matrimonial' => $charge['statut_matrimonial'] ?? 'non_renseigne',
            'revenu_regularite' => $charge['revenu_regularite'] ?? 'non_renseigne',
            'telephone_partage' => $charge['telephone_partage'] ?? false,
        ]);
    }

    /* ---------------------------------------------------------------------- */
    /* Le terrain : activites, groupes, foyers, visites, signalements          */
    /* ---------------------------------------------------------------------- */

    /**
     * Une activite de terrain.
     *
     * L'arrondissement vient TOUJOURS du compte du facilitateur, jamais de la
     * requete. C'est la meme regle que pour l'enregistrement d'un facilitateur
     * par son superviseur : rien de ce qui vient du client ne doit pouvoir
     * deplacer une donnee hors de la portee de celui qui l'envoie.
     */
    private function activite(array $evenement, array $charge, Facilitateur $facilitateur, Carbon $recuA): void
    {
        $touches = (int) $charge['nb_parents_touches'];
        $hommes = (int) ($charge['nb_hommes'] ?? 0);
        $femmes = (int) ($charge['nb_femmes'] ?? 0);
        $handicap = (int) ($charge['nb_participants_handicap'] ?? 0);

        // Un total incoherent est refuse plutot que corrige : 12 hommes et 23
        // femmes sur 30 personnes veut dire qu'une saisie est fausse, et deviner
        // laquelle produirait un chiffre que personne n'a jamais compte.
        abort_if($hommes + $femmes > $touches, 422, 'repartition_par_sexe_incoherente');
        abort_if($handicap > $touches, 422, 'participants_handicap_incoherent');

        $cohorte = $this->cohorteDuFacilitateur($charge['cohorte_id'] ?? null, $facilitateur);

        Activite::create([
            'uuid' => $evenement['uuid'],
            'facilitateur_id' => $facilitateur->id,
            'arrondissement_id' => $facilitateur->arrondissement_id,
            'cohorte_id' => $cohorte?->id,
            'type' => TypeActivite::from($charge['type']),
            'date' => $charge['date'],
            'lieu' => $charge['lieu'],
            'duree_minutes' => $charge['duree_minutes'],
            'nb_parents_touches' => $touches,
            'nb_hommes' => $hommes,
            'nb_femmes' => $femmes,
            'nb_participants_handicap' => $handicap,
            'commentaire' => $charge['commentaire'] ?? null,
            'recue_a' => $recuA,
        ]);

        // Une reunion de groupe fait avancer la continuite du dossier. C'est le
        // seul endroit ou `derniere_reunion` bouge : elle est deduite du
        // terrain, jamais saisie a la main dans un ecran d'administration.
        if ($charge['type'] === TypeActivite::ReunionGsp->value && filled($charge['gsp_uuid'] ?? null)) {
            $this->marquerLaReunion($charge['gsp_uuid'], $charge['date'], $facilitateur);
        }

        $this->avancerLaDerniereActivite($facilitateur, Carbon::parse($charge['date']));
    }

    private function groupeSoutien(array $evenement, array $charge, Facilitateur $facilitateur): void
    {
        $cohorte = $this->cohorteDuFacilitateur($charge['cohorte_id'] ?? null, $facilitateur);

        $groupe = GroupeSoutien::create([
            'uuid' => $evenement['uuid'],
            'libelle' => $charge['libelle'],
            'cohorte_id' => $cohorte?->id,
            'arrondissement_id' => $facilitateur->arrondissement_id,
            'facilitateur_id' => $facilitateur->id,
            'date_creation' => $charge['date_creation'],
            // Un groupe qui vient d'etre cree ne s'est pas encore reuni. Le
            // dire vaut mieux que d'inventer une date de creation-reunion.
            'derniere_reunion' => null,
        ]);

        // Les membres sont des parents de la cohorte du facilitateur, designes
        // par leur code. Un code inconnu est ignore : on ne fabrique pas de
        // parent a partir d'une liste de membres.
        $parents = ParentProgramme::whereIn('code_parent', $charge['membres'] ?? [])
            ->when($cohorte, fn ($q) => $q->where('cohorte_id', $cohorte->id))
            ->pluck('id');

        $groupe->membres()->sync($parents);
    }

    /**
     * Un dossier de foyer. AUCUN nom, aucune adresse, aucune position.
     *
     * Les champs nominatifs ne sont pas « ignores » : le modele n'a aucune
     * colonne ou les mettre. Ce qui arrive en trop dans la charge tombe.
     */
    private function foyer(array $evenement, array $charge, Facilitateur $facilitateur, Carbon $recuA): void
    {
        $difficultes = collect($charge['difficultes_fonctionnelles_foyer'] ?? [])
            ->map(fn ($valeur) => DifficulteFonctionnelle::tryFrom((string) $valeur)?->value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        Foyer::create([
            'uuid' => $evenement['uuid'],
            'facilitateur_id' => $facilitateur->id,
            'arrondissement_id' => $facilitateur->arrondissement_id,
            'localite' => $charge['localite'],
            'nb_adultes' => $charge['nb_adultes'],
            'nb_enfants' => $charge['nb_enfants'],
            'difficultes_fonctionnelles_foyer' => $difficultes,
            'deja_suivi_programme' => (bool) ($charge['deja_suivi_programme'] ?? false),
            'recue_a' => $recuA,
        ]);
    }

    private function visite(array $evenement, array $charge, Facilitateur $facilitateur, Carbon $recuA): void
    {
        $foyer = Foyer::where('uuid', $charge['foyer_uuid'])->firstOrFail();

        abort_unless($foyer->facilitateur_id === $facilitateur->id, 403, 'foyer_non_suivi');

        Visite::create([
            'uuid' => $evenement['uuid'],
            'foyer_id' => $foyer->id,
            'facilitateur_id' => $facilitateur->id,
            'date' => $charge['date'],
            // Des cases cochees, jamais un recit : un champ libre finit par
            // contenir un nom, une rue, un detail qui reidentifie.
            'observations_structurees' => array_values(array_filter(
                (array) ($charge['observations_structurees'] ?? []),
                'is_string',
            )),
            'suivi_prevu' => (bool) ($charge['suivi_prevu'] ?? false),
            'recue_a' => $recuA,
        ]);

        $this->avancerLaDerniereActivite($facilitateur, Carbon::parse($charge['date']));
    }

    /**
     * Un signalement. Il REMONTE, il ne declenche rien.
     *
     * Aucune autorite n'est notifiee, aucun message ne part. Le signalement
     * entre dans la file du superviseur de l'arrondissement, et c'est un humain
     * qui juge. Il n'y a pas non plus d'identite a porter : type, gravite,
     * arrondissement.
     */
    private function signalement(array $evenement, array $charge, Facilitateur $facilitateur, Carbon $recuA): void
    {
        $activite = filled($charge['activite_uuid'] ?? null)
            ? Activite::where('uuid', $charge['activite_uuid'])
                ->where('facilitateur_id', $facilitateur->id)
                ->first()
            : null;

        Signalement::create([
            'uuid' => $evenement['uuid'],
            'activite_id' => $activite?->id,
            'facilitateur_id' => $facilitateur->id,
            'arrondissement_id' => $facilitateur->arrondissement_id,
            'type' => TypeSignalement::from($charge['type']),
            'gravite' => GraviteSignalement::from($charge['gravite']),
            'statut' => StatutSignalement::Soumis,
            'recue_a' => $recuA,
        ]);
    }

    /**
     * La progression d'un facilitateur dans un module de formation.
     *
     * ROUVRIR UN MODULE EST UNE ACTIVITE. C'est le point du brief : un
     * facilitateur forme il y a deux ans ne se refait pas former, il rouvre ses
     * modules — et ce faisant il rouvre l'application, donc il reste actif dans
     * le registre. Ne pas compter cette ouverture reviendrait a le declarer
     * inactif au moment precis ou il se remet a jour.
     *
     * Un module non valide n'est jamais servi, donc jamais suivi : la
     * progression est refusee plutot que de creer une trace sur un contenu qui
     * n'aurait pas du sortir.
     */
    private function progressionFormation(array $charge, Facilitateur $facilitateur): void
    {
        $module = ModuleFormation::diffusables()
            ->where('code', $charge['module_code'])
            ->firstOrFail();

        $vues = collect((array) ($charge['sections_vues'] ?? []))
            // Une fermeture, pas 'is_numeric' : Collection::filter passe la
            // valeur ET la clé, et la fonction native n'en accepte qu'une.
            ->filter(fn ($o) => is_numeric($o))
            ->map(fn ($o) => (int) $o)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $ouverte = Carbon::parse($charge['ouverte_a'] ?? now());

        $progression = ProgressionFormation::firstOrNew([
            'facilitateur_id' => $facilitateur->id,
            'module_formation_id' => $module->id,
        ]);

        // Les sections vues s'ajoutent, elles ne se remplacent pas : une
        // remontee tardive ne doit pas effacer une lecture plus recente.
        $progression->sections_vues = collect($progression->sections_vues ?? [])
            ->merge($vues)->unique()->sort()->values()->all();

        if ($progression->derniere_ouverture === null
            || $progression->derniere_ouverture->lt($ouverte)) {
            $progression->derniere_ouverture = $ouverte;
        }

        // Termine des que toutes les sections ont ete vues. On ne demande pas
        // au facilitateur de le declarer : il l'a fait, cela suffit.
        if ($progression->termine_a === null
            && count($progression->sections_vues) >= $module->sections()->count()) {
            $progression->termine_a = $ouverte;
        }

        $progression->save();

        $this->avancerLaDerniereActivite($facilitateur, $ouverte);
    }

    /* ---------------------------------------------------------------------- */

    /** Une cohorte ne peut etre designee que si elle appartient au facilitateur. */
    private function cohorteDuFacilitateur(?int $cohorteId, Facilitateur $facilitateur): ?Cohorte
    {
        if ($cohorteId === null) {
            return null;
        }

        $cohorte = Cohorte::findOrFail($cohorteId);

        abort_unless($cohorte->facilitateur_id === $facilitateur->id, 403, 'cohorte_non_attribuee');

        return $cohorte;
    }

    private function marquerLaReunion(string $gspUuid, string $date, Facilitateur $facilitateur): void
    {
        $groupe = GroupeSoutien::where('uuid', $gspUuid)->first();

        if ($groupe === null || $groupe->arrondissement_id !== $facilitateur->arrondissement_id) {
            return;
        }

        $jour = Carbon::parse($date);

        // Vers l'avant seulement : une reunion ancienne remontee en retard ne
        // doit pas faire reculer la continuite du dossier.
        if ($groupe->derniere_reunion === null || $groupe->derniere_reunion->lt($jour)) {
            $groupe->forceFill(['derniere_reunion' => $jour])->save();
        }
    }

    /**
     * Une activite remontee est une preuve d'activite, au meme titre qu'une
     * seance. Un facilitateur qui ne fait que des causeries est actif.
     */
    private function avancerLaDerniereActivite(Facilitateur $facilitateur, Carbon $date): void
    {
        if ($facilitateur->derniere_activite === null || $facilitateur->derniere_activite->lt($date)) {
            $facilitateur->forceFill(['derniere_activite' => $date])->save();
        }
    }

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
            'charge' => $this->sansLesSecrets($evenement['charge'] ?? []),
            'emis_a' => Carbon::parse($evenement['emis_a']),
            'recu_a' => $recuA,
        ]);
    }

    /**
     * Le journal garde tout, sauf ce qui est un secret.
     *
     * Remplacer plutôt que retirer : la trace reste lisible — on voit qu'un
     * code a bien été transmis, sans pouvoir le relire.
     *
     * @param  array<string, mixed>  $charge
     * @return array<string, mixed>
     */
    private function sansLesSecrets(array $charge): array
    {
        foreach (self::CHAMPS_SECRETS as $champ) {
            if (array_key_exists($champ, $charge)) {
                $charge[$champ] = '[secret, non conservé]';
            }
        }

        return $charge;
    }
}
