<?php

namespace Database\Seeders;

use App\Enums\StatutPresence;
use App\Models\Cohorte;
use App\Services\ReceptionEvenements;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Des séances tenues ailleurs qu'à Ebolowa II.
 *
 * Comme `SeanceSeeder`, ce seeder n'écrit rien directement : il rejoue la file
 * d'événements qu'un kit hors ligne aurait remontée, et laisse
 * `ReceptionEvenements` faire le reste. Un seul chemin d'écriture, y compris
 * pour les données de démonstration — c'est ce qui fait que le jeu de démo
 * prouve quelque chose au lieu de le simuler.
 *
 * Pourquoi c'est nécessaire : sans séances hors d'Ebolowa II, la dose par
 * parent et l'écart déclaré/observé valent zéro partout ailleurs, et le
 * tableau de bord d'une délégation départementale est une page de zéros. Le
 * mécanisme de portée n'a alors rien à montrer.
 *
 * Les taux de présence et les écarts varient d'une cohorte à l'autre à dessein.
 * Un tableau de bord où toutes les lignes se ressemblent n'apprend rien à
 * personne : ce qu'une délégation cherche, c'est la ligne qui sort du lot.
 */
class SeancesDuSudSeeder extends Seeder
{
    /** Le nombre de cohortes qui reçoivent des séances, celle d'Ebolowa II exclue. */
    private const COHORTES_ANIMEES = 12;

    public function run(ReceptionEvenements $reception): void
    {
        $demonstration = Cohorte::where('libelle', 'Ebolowa II — groupe du mardi')->value('id');

        // Une cohorte sur cinq, pour que les territoires ne se ressemblent pas.
        $cohortes = Cohorte::whereKeyNot($demonstration)
            ->with('parents', 'facilitateur')
            ->orderBy('id')
            ->get()
            ->filter(fn (Cohorte $c, int $rang) => $rang % 5 === 0)
            ->take(self::COHORTES_ANIMEES);

        foreach ($cohortes->values() as $rang => $cohorte) {
            $module = $cohorte->curriculumVersion->modules()->where('numero', 8)->firstOrFail();
            $sequences = $module->sequences()->ordonnees()->get()->keyBy('ordre');

            // Une à trois séances, selon la cohorte.
            for ($numero = 1; $numero <= 1 + $rang % 3; $numero++) {
                $this->seance($reception, $cohorte, $module->id, $sequences, $rang, $numero);
            }
        }
    }

    private function seance(
        ReceptionEvenements $reception,
        Cohorte $cohorte,
        int $moduleId,
        $sequences,
        int $rang,
        int $numero,
    ): void {
        // Les séances sont datées dans le passé, et la remontée l'est aussi :
        // `ReceptionEvenements` ne fait avancer la dernière activité que vers
        // l'avant, donc l'étalement du registre reste intact.
        $debut = Carbon::now()
            ->subDays(70 - $numero * 14 - $rang % 9)
            ->setTime(15, 0);

        $recuA = $debut->copy()->addDays(($rang + $numero) % 6)->addHours(4);

        $seanceUuid = (string) Str::uuid();
        $file = [[
            'uuid' => $seanceUuid,
            'type' => 'seance',
            'seance_uuid' => null,
            'emis_a' => $debut->toIso8601String(),
            'charge' => [
                'cohorte_id' => $cohorte->id,
                'module_id' => $moduleId,
                'date' => $debut->toDateString(),
            ],
        ]];

        // Le pointage. Le taux d'absence varie d'un lieu à l'autre : c'est
        // précisément ce qu'une délégation cherche à repérer.
        $pointeA = $debut->copy()->addMinutes(6);

        foreach ($cohorte->parents->values() as $index => $parent) {
            $graine = $rang * 17 + $numero * 5 + $index;

            $statut = match (true) {
                $graine % (7 + $rang % 5) === 0 => StatutPresence::Absent,
                $graine % 11 === 0 => StatutPresence::RattrapeBinome,
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

        // L'OBSERVÉ, puis LE DÉCLARÉ. Une cohorte sur trois porte un écart réel :
        // la séquence 4 est déclarée réalisée sans avoir jamais été ouverte.
        $porteUnEcart = $rang % 3 === 0;
        $ouvertes = $porteUnEcart ? [1, 2, 3, 5] : [1, 2, 3, 4, 5];
        $decalage = 0;

        foreach ($ouvertes as $ordre) {
            $ouverteA = $debut->copy()->addMinutes($decalage);
            $decalage += 18;

            $file[] = [
                'uuid' => (string) Str::uuid(),
                'type' => 'sequence_ouverte',
                'seance_uuid' => $seanceUuid,
                'emis_a' => $ouverteA->toIso8601String(),
                'charge' => [
                    'sequence_id' => $sequences[$ordre]->id,
                    'ouverte_a' => $ouverteA->toIso8601String(),
                    'duree_reelle_secondes' => 900 + ($ordre * 120),
                ],
            ];
        }

        $declareA = $debut->copy()->addMinutes(100);

        foreach ($sequences as $ordre => $sequence) {
            $file[] = [
                'uuid' => (string) Str::uuid(),
                'type' => 'fiche_fidelite',
                'seance_uuid' => $seanceUuid,
                'emis_a' => $declareA->toIso8601String(),
                'charge' => [
                    'sequence_id' => $sequence->id,
                    'realisee_bool' => true,
                    'note_qualite' => 2 + ($rang + $ordre) % 2,
                    'commentaire' => null,
                ],
            ];
        }

        $bilan = $reception->recevoir($file, $cohorte->facilitateur, $recuA);

        if ($bilan['rejetes'] !== []) {
            throw new \RuntimeException(
                'Séance de démonstration rejetée : '.json_encode($bilan['rejetes'])
            );
        }
    }
}
