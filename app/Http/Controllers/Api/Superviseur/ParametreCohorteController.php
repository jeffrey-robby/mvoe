<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Http\Controllers\Controller;
use App\Models\Cohorte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le paramètre de ratio.
 *
 * Le plafond d'une cohorte est une DONNÉE, jamais une constante : aucun 20
 * n'est écrit dans le code, et la migration ne lui donne même pas de valeur
 * par défaut. Passer une cohorte de 20 à 10 se fait donc ici, sans
 * déploiement — c'est la manipulation faite en direct devant le jury.
 */
class ParametreCohorteController extends Controller
{
    public function update(Request $request, Cohorte $cohorte): JsonResponse
    {
        $donnees = $request->validate([
            'ratio_max' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $ancien = $cohorte->ratio_max;
        $cohorte->update($donnees);

        return response()->json([
            'cohorte' => [
                'id' => $cohorte->id,
                'libelle' => $cohorte->libelle,
                'ratio_max' => $cohorte->ratio_max,
                'effectif' => $cohorte->parents()->count(),
                'places_restantes' => $cohorte->placesRestantes(),
                // Baisser le plafond sous l'effectif déjà inscrit ne supprime
                // personne : on le signale, on ne corrige rien dans le dos du
                // superviseur.
                'effectif_au_dela_du_plafond' => max(0, $cohorte->parents()->count() - $cohorte->ratio_max),
            ],
            'modification' => ['ratio_max' => ['avant' => $ancien, 'apres' => $cohorte->ratio_max]],
        ]);
    }
}
