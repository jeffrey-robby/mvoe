<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Http\Controllers\Controller;
use App\Models\Cohorte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les cohortes vues par la délégation.
 *
 * Sert l'écran de paramètres : sans cette liste, le superviseur n'aurait aucun
 * moyen de désigner la cohorte dont il veut changer le plafond.
 */
class CohorteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perimetre = $request->user()->arrondissement;

        $cohortes = Cohorte::query()
            ->when($perimetre !== null, fn ($q) => $q->where('arrondissement', $perimetre))
            ->when($request->string('arrondissement')->isNotEmpty(),
                fn ($q) => $q->where('arrondissement', $request->string('arrondissement')))
            ->with('facilitateur:id,nom')
            ->withCount('parents', 'seances')
            ->orderBy('arrondissement')
            ->orderBy('libelle')
            ->get();

        return response()->json([
            'cohortes' => $cohortes->map(fn (Cohorte $c) => [
                'id' => $c->id,
                'libelle' => $c->libelle,
                'arrondissement' => $c->arrondissement,
                'date_debut' => $c->date_debut->toDateString(),
                'facilitateur' => $c->facilitateur?->nom,
                'effectif' => $c->parents_count,
                'seances_tenues' => $c->seances_count,
                // Le plafond est une DONNÉE de la cohorte, pas une constante du
                // code : c'est ce qui permet de le changer devant le jury sans
                // le moindre déploiement.
                'ratio_max' => $c->ratio_max,
                'places_restantes' => $c->placesRestantes(),
                'effectif_au_dela_du_plafond' => max(0, $c->parents_count - $c->ratio_max),
            ])->values()->all(),
        ]);
    }
}
