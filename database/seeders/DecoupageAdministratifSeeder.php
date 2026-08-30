<?php

namespace Database\Seeders;

use App\Models\Arrondissement;
use App\Models\Departement;
use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * Le découpage administratif réel.
 *
 * Les dix régions du Cameroun, dont la seule région du Sud est peuplée : ses
 * quatre départements et ses vingt-neuf arrondissements. Les neuf autres
 * existent en libellé seul — elles montrent dans l'interface du MINPROFF que le
 * système est national par construction, sans faire croire qu'elles portent des
 * données.
 */
class DecoupageAdministratifSeeder extends Seeder
{
    private const REGIONS = [
        ['AD', 'Adamaoua'],
        ['CE', 'Centre'],
        ['ES', 'Est'],
        ['EN', 'Extrême-Nord'],
        ['LT', 'Littoral'],
        ['NO', 'Nord'],
        ['NW', 'Nord-Ouest'],
        ['OU', 'Ouest'],
        ['SU', 'Sud'],
        ['SW', 'Sud-Ouest'],
    ];

    /** Les 4 départements du Sud et leurs 29 arrondissements. */
    private const SUD = [
        'Mvila' => [
            'Ebolowa I', 'Ebolowa II', 'Biwong-Bane', 'Biwong-Bulu',
            'Efoulan', 'Mengong', 'Mvangan', 'Ngoulemakong',
        ],
        'Dja-et-Lobo' => [
            'Sangmélima', 'Bengbis', 'Djoum', 'Meyomessala',
            'Meyomessi', 'Mintom', 'Oveng', 'Zoétélé',
        ],
        'Océan' => [
            'Kribi I', 'Kribi II', 'Akom II', 'Bipindi', 'Campo',
            'Lokoundjé', 'Lolodorf', 'Mvengue', 'Nyété',
        ],
        'Vallée-du-Ntem' => [
            'Ambam', 'Kyé-Ossi', "Ma'an", 'Olamze',
        ],
    ];

    public function run(): void
    {
        $sud = null;

        foreach (self::REGIONS as [$code, $libelle]) {
            $region = Region::create([
                'code' => $code,
                'libelle' => $libelle,
                'peuplee' => $code === 'SU',
            ]);

            if ($code === 'SU') {
                $sud = $region;
            }
        }

        foreach (self::SUD as $libelle => $arrondissements) {
            $departement = Departement::create([
                'region_id' => $sud->id,
                'libelle' => $libelle,
            ]);

            foreach ($arrondissements as $nom) {
                Arrondissement::create([
                    'departement_id' => $departement->id,
                    // Dénormalisé : évite une jointure sur toute requête de
                    // portée régionale, et un arrondissement ne change pas de
                    // région.
                    'region_id' => $sud->id,
                    'libelle' => $nom,
                ]);
            }
        }
    }
}
