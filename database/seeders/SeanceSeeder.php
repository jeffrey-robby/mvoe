<?php

namespace Database\Seeders;

use App\Enums\StatutPresence;
use App\Models\Cohorte;
use App\Models\EvenementSync;
use App\Models\FicheFidelite;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Sequence;
use App\Models\SequenceOuverte;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Trois séances déjà tenues, remontées et projetées en base.
 *
 * Chaque séance porte deux sources indépendantes :
 *   - l'OBSERVÉ (`sequences_ouvertes`), écrit passivement pendant la séance ;
 *   - le DÉCLARÉ (`fiches_fidelite`), saisi de mémoire après la séance.
 *
 * La deuxième séance présente un écart réel entre les deux, dans les deux sens.
 * C'est ce qu'aucun formulaire papier ne peut produire : le papier n'a qu'une
 * seule source, donc rien à confronter.
 *
 * Note sur les données de démonstration : les trois séances portent toutes sur
 * le module 8, seul module dont le déroulé est écrit. En production elles
 * porteraient sur trois modules successifs.
 */
class SeanceSeeder extends Seeder
{
    /**
     * `ouvertures`   : ordre de séquence => [minutes après le début, durée réelle en secondes]
     * `declarations` : ordre de séquence => [réalisée, note de qualité sur 3, commentaire]
     * Une séquence absente de `ouvertures` n'a jamais été ouverte pendant la séance.
     */
    private const SEANCES = [
        [
            'date' => '2026-07-14',
            'recue_a' => '2026-07-16 10:12:00',
            'ouvertures' => [
                1 => [0, 660],
                2 => [11, 1_260],
                3 => [32, 1_620],
                4 => [59, 1_140],
                5 => [78, 900],
            ],
            'declarations' => [
                1 => [true, 3, null],
                2 => [true, 3, null],
                3 => [true, 2, 'Les parents ont beaucoup parlé, on a débordé.'],
                4 => [true, 3, null],
                5 => [true, 3, null],
            ],
            'bilan' => 'Rien à signaler, le groupe est très participatif.',
            'absents' => ['EB2-13'],
            'rattrapes' => ['EB2-07'],
        ],
        [
            'date' => '2026-07-28',
            'recue_a' => '2026-08-06 09:03:00',
            'ouvertures' => [
                1 => [0, 720],
                2 => [12, 1_380],
                // Séquence 3 ouverte pendant la séance, mais déclarée non réalisée.
                3 => [35, 900],
                // Séquence 4 JAMAIS ouverte, et pourtant déclarée réalisée.
                5 => [51, 780],
            ],
            'declarations' => [
                1 => [true, 3, null],
                2 => [true, 2, null],
                3 => [false, null, "Pas eu le temps d'aborder cette partie."],
                4 => [true, 2, null],
                5 => [true, 2, null],
            ],
            'bilan' => 'Séance écourtée, la pluie sur la tôle couvrait les voix.',
            'absents' => ['EB2-02', 'EB2-13', 'EB2-20'],
            'rattrapes' => ['EB2-06', 'EB2-09'],
        ],
        [
            'date' => '2026-08-21',
            'recue_a' => '2026-08-21 19:40:00',
            'ouvertures' => [
                1 => [0, 600],
                2 => [10, 1_200],
                3 => [30, 1_500],
                4 => [56, 1_080],
            ],
            'declarations' => [
                1 => [true, 3, null],
                2 => [true, 3, null],
                3 => [true, 3, null],
                4 => [true, 2, null],
                5 => [false, null, 'Coupé faute de temps, reporté à la semaine prochaine.'],
            ],
            'bilan' => 'La dernière séquence saute trop souvent, il faut démarrer plus tôt.',
            'absents' => ['EB2-08', 'EB2-16'],
            'rattrapes' => ['EB2-15'],
        ],
    ];

    public function run(): void
    {
        $cohorte = Cohorte::with('parents')->firstOrFail();
        $module = $cohorte->curriculumVersion->modules()->where('numero', 8)->firstOrFail();
        $sequences = $module->sequences()->ordonnees()->get()->keyBy('ordre');

        foreach (self::SEANCES as $donnees) {
            $this->seance($cohorte, $module->id, $sequences, $donnees);
        }
    }

    private function seance(Cohorte $cohorte, int $moduleId, $sequences, array $donnees): void
    {
        // La séance commence à 15 h, comme toutes les séances du groupe du mardi.
        $debut = Carbon::parse($donnees['date'].' 15:00:00');
        $recuA = Carbon::parse($donnees['recue_a']);

        $seance = Seance::create([
            'uuid' => (string) Str::uuid(),
            'cohorte_id' => $cohorte->id,
            'module_id' => $moduleId,
            'date' => $donnees['date'],
            'facilitateur_id' => $cohorte->facilitateur_id,
            'recue_a' => $recuA,
        ]);

        $this->journaliser($seance, 'seance', $debut, $recuA, [
            'cohorte_id' => $cohorte->id,
            'module_id' => $moduleId,
            'date' => $donnees['date'],
        ]);

        $this->pointage($seance, $cohorte, $donnees, $debut, $recuA);
        $this->observe($seance, $sequences, $donnees, $debut, $recuA);
        $this->declare($seance, $sequences, $donnees, $debut, $recuA);
    }

    /**
     * Le pointage se fait pendant la séance, d'un geste par parent.
     * Un parent rattrapé par son binôme a reçu la séance, autrement.
     */
    private function pointage(Seance $seance, Cohorte $cohorte, array $donnees, Carbon $debut, Carbon $recuA): void
    {
        $emisA = $debut->copy()->addMinutes(6);

        foreach ($cohorte->parents as $parent) {
            $statut = match (true) {
                in_array($parent->code_parent, $donnees['absents'], true) => StatutPresence::Absent,
                in_array($parent->code_parent, $donnees['rattrapes'], true) => StatutPresence::RattrapeBinome,
                default => StatutPresence::Present,
            };

            $uuid = (string) Str::uuid();

            Presence::create([
                'uuid' => $uuid,
                'seance_id' => $seance->id,
                'parent_id' => $parent->id,
                'statut' => $statut,
            ]);

            $this->journaliser($seance, 'presence', $emisA, $recuA, [
                'code_parent' => $parent->code_parent,
                'statut' => $statut->value,
            ], $uuid);
        }
    }

    /**
     * L'OBSERVÉ. Une ligne par ouverture réelle de séquence, écrite au moment
     * où le facilitateur ouvre le bloc. Il ne déclare rien ici.
     */
    private function observe(Seance $seance, $sequences, array $donnees, Carbon $debut, Carbon $recuA): void
    {
        foreach ($donnees['ouvertures'] as $ordre => [$decalage, $duree]) {
            $ouverteA = $debut->copy()->addMinutes($decalage);
            $uuid = (string) Str::uuid();

            SequenceOuverte::create([
                'uuid' => $uuid,
                'seance_id' => $seance->id,
                'sequence_id' => $sequences[$ordre]->id,
                'ouverte_a' => $ouverteA,
                'duree_reelle_secondes' => $duree,
            ]);

            $this->journaliser($seance, 'sequence_ouverte', $ouverteA, $recuA, [
                'sequence_ordre' => $ordre,
                'ouverte_a' => $ouverteA->toIso8601String(),
                'duree_reelle_secondes' => $duree,
            ], $uuid);
        }
    }

    /**
     * LE DÉCLARÉ. Rempli après la séance : une réponse par séquence, plus une
     * ligne sans séquence qui porte le champ libre de fin de séance.
     */
    private function declare(Seance $seance, $sequences, array $donnees, Carbon $debut, Carbon $recuA): void
    {
        $emisA = $debut->copy()->addMinutes(100);

        foreach ($donnees['declarations'] as $ordre => [$realisee, $note, $commentaire]) {
            $uuid = (string) Str::uuid();

            FicheFidelite::create([
                'uuid' => $uuid,
                'seance_id' => $seance->id,
                'sequence_id' => $sequences[$ordre]->id,
                'realisee_bool' => $realisee,
                'note_qualite' => $note,
                'commentaire' => $commentaire,
            ]);

            $this->journaliser($seance, 'fiche_fidelite', $emisA, $recuA, [
                'sequence_ordre' => $ordre,
                'realisee_bool' => $realisee,
                'note_qualite' => $note,
                'commentaire' => $commentaire,
            ], $uuid);
        }

        // « Qu'est-ce qui a le moins bien marché ? » — vaut pour toute la séance,
        // d'où l'absence de séquence rattachée.
        $uuid = (string) Str::uuid();

        FicheFidelite::create([
            'uuid' => $uuid,
            'seance_id' => $seance->id,
            'sequence_id' => null,
            'realisee_bool' => null,
            'note_qualite' => null,
            'commentaire' => $donnees['bilan'],
        ]);

        $this->journaliser($seance, 'bilan_seance', $emisA, $recuA, [
            'commentaire' => $donnees['bilan'],
        ], $uuid);
    }

    /**
     * Chaque écriture est doublée d'un événement dans le journal de remontée.
     * Le journal conserve ce que le client a réellement envoyé : les tables
     * métier n'en sont que la projection courante, et rien n'est jamais écrasé.
     */
    private function journaliser(Seance $seance, string $type, Carbon $emisA, Carbon $recuA, array $charge, ?string $uuid = null): void
    {
        EvenementSync::create([
            'uuid' => $uuid ?? $seance->uuid,
            'type' => $type,
            'seance_uuid' => $seance->uuid,
            'charge' => $charge,
            'emis_a' => $emisA,
            'recu_a' => $recuA,
        ]);
    }
}
