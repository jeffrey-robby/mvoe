<?php

namespace App\Services;

use App\Enums\StatutCampagne;
use App\Models\Arrondissement;
use App\Models\Campagne;
use App\Models\CampagneAffectation;
use App\Models\Departement;
use App\Models\Facilitateur;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

/**
 * Le déclenchement d'une campagne sur une ou plusieurs régions.
 *
 * **Aucune logique de propagation.** Le brief est explicite là-dessus, et il a
 * raison : une file d'attente qui ferait « descendre » une campagne d'échelon
 * en échelon simulerait une administration au lieu de la servir. Dans la vraie
 * vie, la cascade n'est pas un processus asynchrone — c'est un fait
 * administratif. Le ministère décide, et tous les échelons sont concernés au
 * même instant.
 *
 * On crée donc TOUTES les affectations d'un coup, à tous les niveaux, et
 * l'écran montre qui a pris connaissance. C'est cette prise de connaissance
 * qui avance dans le temps, pas la campagne.
 */
class DeclenchementCampagne
{
    /**
     * @param  array<int, int>  $regionIds  Les régions visées.
     * @return int Le nombre d'affectations créées.
     */
    public function declencher(Campagne $campagne, array $regionIds): int
    {
        return DB::transaction(function () use ($campagne, $regionIds) {
            $regions = Region::whereIn('id', $regionIds)->pluck('id');
            $departements = Departement::whereIn('region_id', $regions)->pluck('id');
            $arrondissements = Arrondissement::whereIn('region_id', $regions)->pluck('id');
            $facilitateurs = Facilitateur::whereIn('arrondissement_id', $arrondissements)->pluck('id');

            $lignes = collect([
                'region' => $regions,
                'departement' => $departements,
                'arrondissement' => $arrondissements,
                'facilitateur' => $facilitateurs,
            ])->flatMap(fn ($ids, $niveau) => $ids->map(fn (int $id) => [
                'campagne_id' => $campagne->id,
                'niveau' => $niveau,
                'entite_id' => $id,
                'statut' => 'affectee',
                // Personne n'a encore pris connaissance : c'est précisément ce
                // que l'écran doit montrer, plutôt qu'une cascade cochée
                // d'avance qui ferait croire au travail fait.
                'date_reception' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]))->all();

            // `insertOrIgnore` : redéclencher une campagne sur une région déjà
            // couverte ne doit ni échouer, ni effacer les prises de
            // connaissance déjà enregistrées.
            CampagneAffectation::insertOrIgnore($lignes);

            $campagne->update(['statut' => StatutCampagne::Declenchee]);

            return count($lignes);
        });
    }

    /**
     * Un échelon prend connaissance.
     *
     * Le seul geste qui fait avancer une campagne. Il est manuel et il le
     * reste : cocher automatiquement à l'ouverture d'un écran ferait passer
     * une consultation pour une décision.
     */
    public function marquerRecue(Campagne $campagne, string $niveau, int $entiteId): bool
    {
        $affectation = CampagneAffectation::where('campagne_id', $campagne->id)
            ->where('niveau', $niveau)
            ->where('entite_id', $entiteId)
            ->whereNull('date_reception')
            ->first();

        if ($affectation === null) {
            return false;
        }

        $affectation->update(['date_reception' => now(), 'statut' => 'recue']);

        return true;
    }
}
