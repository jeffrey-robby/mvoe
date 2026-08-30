<?php

namespace Database\Seeders;

use App\Enums\StatutPresence;
use App\Models\Cohorte;
use App\Services\ReceptionEvenements;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Trois séances déjà tenues, rejouées exactement comme le kit les aurait
 * remontées.
 *
 * Ce seeder n'écrit RIEN directement en base : il fabrique la file
 * d'événements qu'un facilitateur hors ligne aurait accumulée, puis la passe
 * à ReceptionEvenements. Il n'existe donc qu'un seul chemin d'écriture des
 * données de séance, et le jeu de démonstration prouve que ce chemin marche.
 *
 * Chaque séance porte deux sources indépendantes :
 *   - l'OBSERVÉ (`sequences_ouvertes`), écrit passivement pendant la séance ;
 *   - le DÉCLARÉ (`fiches_fidelite`), saisi de mémoire après la séance.
 *
 * La deuxième séance présente un écart réel entre les deux, dans les deux sens.
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

    public function run(ReceptionEvenements $reception): void
    {
        // Explicitement celle de la démonstration : depuis que la région entière
        // est peuplée, « la première cohorte » n'est plus une désignation sûre.
        $cohorte = Cohorte::with('parents', 'facilitateur')
            ->where('libelle', 'Ebolowa II — groupe du mardi')
            ->firstOrFail();
        $module = $cohorte->curriculumVersion->modules()->where('numero', 8)->firstOrFail();
        $sequences = $module->sequences()->ordonnees()->get()->keyBy('ordre');

        foreach (self::SEANCES as $donnees) {
            $bilan = $reception->recevoir(
                $this->fileDEvenements($cohorte, $module->id, $sequences, $donnees),
                $cohorte->facilitateur,
                // Horodatage de réception d'origine : c'est lui qui donne au
                // rapport ses délais de remontée de 2, 9 et 0 jours.
                Carbon::parse($donnees['recue_a']),
            );

            if ($bilan['rejetes'] !== []) {
                throw new \RuntimeException(
                    'Séance de démonstration rejetée : '.json_encode($bilan['rejetes'])
                );
            }
        }
    }

    /**
     * La file exacte qu'un kit hors ligne aurait accumulée pendant et après la
     * séance, dans l'ordre où les gestes ont eu lieu.
     *
     * @return array<int, array>
     */
    private function fileDEvenements(Cohorte $cohorte, int $moduleId, $sequences, array $donnees): array
    {
        // La séance commence à 15 h, comme toutes les séances du groupe du mardi.
        $debut = Carbon::parse($donnees['date'].' 15:00:00');
        $seanceUuid = (string) Str::uuid();

        $file = [[
            'uuid' => $seanceUuid,
            'type' => 'seance',
            'seance_uuid' => null,
            'emis_a' => $debut->toIso8601String(),
            'charge' => [
                'cohorte_id' => $cohorte->id,
                'module_id' => $moduleId,
                'date' => $donnees['date'],
            ],
        ]];

        // Le pointage, d'un geste par parent, en début de séance.
        $pointeA = $debut->copy()->addMinutes(6);

        foreach ($cohorte->parents as $parent) {
            $statut = match (true) {
                in_array($parent->code_parent, $donnees['absents'], true) => StatutPresence::Absent,
                in_array($parent->code_parent, $donnees['rattrapes'], true) => StatutPresence::RattrapeBinome,
                default => StatutPresence::Present,
            };

            $file[] = [
                'uuid' => (string) Str::uuid(),
                'type' => 'presence',
                'seance_uuid' => $seanceUuid,
                'emis_a' => $pointeA->toIso8601String(),
                'charge' => ['code_parent' => $parent->code_parent, 'statut' => $statut->value],
            ];
        }

        // L'OBSERVÉ : écrit au moment où le facilitateur ouvre chaque séquence.
        foreach ($donnees['ouvertures'] as $ordre => [$decalage, $duree]) {
            $ouverteA = $debut->copy()->addMinutes($decalage);

            $file[] = [
                'uuid' => (string) Str::uuid(),
                'type' => 'sequence_ouverte',
                'seance_uuid' => $seanceUuid,
                'emis_a' => $ouverteA->toIso8601String(),
                'charge' => [
                    'sequence_id' => $sequences[$ordre]->id,
                    'ouverte_a' => $ouverteA->toIso8601String(),
                    'duree_reelle_secondes' => $duree,
                ],
            ];
        }

        // LE DÉCLARÉ : rempli après la séance, jamais pendant.
        $declareA = $debut->copy()->addMinutes(100);

        foreach ($donnees['declarations'] as $ordre => [$realisee, $note, $commentaire]) {
            $file[] = [
                'uuid' => (string) Str::uuid(),
                'type' => 'fiche_fidelite',
                'seance_uuid' => $seanceUuid,
                'emis_a' => $declareA->toIso8601String(),
                'charge' => [
                    'sequence_id' => $sequences[$ordre]->id,
                    'realisee_bool' => $realisee,
                    'note_qualite' => $note,
                    'commentaire' => $commentaire,
                ],
            ];
        }

        $file[] = [
            'uuid' => (string) Str::uuid(),
            'type' => 'bilan_seance',
            'seance_uuid' => $seanceUuid,
            'emis_a' => $declareA->toIso8601String(),
            'charge' => ['commentaire' => $donnees['bilan']],
        ];

        return $file;
    }
}
