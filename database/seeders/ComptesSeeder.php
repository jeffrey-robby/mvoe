<?php

namespace Database\Seeders;

use App\Models\Arrondissement;
use App\Models\Departement;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Les comptes des quatre niveaux administratifs.
 *
 * PERSONNE NE S'AUTO-INSCRIT. Le seeder crée le compte racine du MINPROFF, qui
 * crée la délégation régionale, qui crée les départementales, qui créent les
 * superviseurs. `cree_par_id` garde cette chaîne, et elle est vérifiable :
 * remonter d'un superviseur jusqu'au MINPROFF doit toujours être possible.
 *
 * Un seul mot de passe pour toute la démonstration — ce sont des comptes
 * fictifs, et devoir en retenir trente-cinq rendrait la démonstration
 * impraticable.
 */
class ComptesSeeder extends Seeder
{
    public const MOT_DE_PASSE = 'mvoe-demo';

    /** L'arrondissement dont le superviseur sert la démonstration. */
    public const ARRONDISSEMENT_DEMO = 'Ebolowa II';

    public function run(): void
    {
        $sud = Region::where('code', 'SU')->firstOrFail();

        // 1. Le compte racine. Le seul que personne n'a créé.
        $minproff = $this->compte(
            'MINPROFF — Programme national de parentalité positive',
            'minproff@mvoe.test',
            'national',
        );

        // 2. La délégation régionale, créée par le MINPROFF.
        $regionale = $this->compte(
            'Délégation régionale du Sud',
            'sud@mvoe.test',
            'region',
            ['region_id' => $sud->id],
            $minproff,
        );

        // 3. Les quatre délégations départementales, créées par la régionale.
        foreach (Departement::where('region_id', $sud->id)->get() as $departement) {
            $departementale = $this->compte(
                "Délégation départementale — {$departement->libelle}",
                Str::slug($departement->libelle).'@mvoe.test',
                'departement',
                ['departement_id' => $departement->id],
                $regionale,
            );

            // 4. Les superviseurs d'arrondissement, créés par leur
            //    délégation départementale. Ce sont eux qui enregistreront
            //    les facilitateurs.
            foreach ($departement->arrondissements as $arrondissement) {
                $this->compte(
                    "Délégation d'arrondissement — {$arrondissement->libelle}",
                    Str::slug($arrondissement->libelle).'@mvoe.test',
                    'arrondissement',
                    ['arrondissement_id' => $arrondissement->id],
                    $departementale,
                );
            }
        }
    }

    private function compte(
        string $nom,
        string $email,
        string $niveau,
        array $portee = [],
        ?User $createur = null,
    ): User {
        return User::create([
            'name' => $nom,
            'email' => $email,
            'password' => Hash::make(self::MOT_DE_PASSE),
            'niveau' => $niveau,
            'cree_par_id' => $createur?->id,
            ...$portee,
        ]);
    }

    /** Le superviseur d'un arrondissement, retrouvé par son libellé. */
    public static function superviseurDe(string $libelleArrondissement): User
    {
        $arrondissement = Arrondissement::where('libelle', $libelleArrondissement)->firstOrFail();

        return User::where('arrondissement_id', $arrondissement->id)->firstOrFail();
    }
}
