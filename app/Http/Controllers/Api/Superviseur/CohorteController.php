<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Http\Controllers\Controller;
use App\Models\Cohorte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les cohortes vues par une délégation, à son niveau de portée.
 *
 * Sert l'écran de paramètres : sans cette liste, personne n'aurait de moyen de
 * désigner la cohorte dont il veut changer le plafond.
 */
class CohorteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $portee = $request->user()->portee();

        $cohortes = Cohorte::query()
            ->dansLaPortee($portee)
            ->when($request->integer('arrondissement_id'),
                fn ($q, $id) => $q->where('arrondissement_id', $id))
            ->with('facilitateur:id,nom', 'arrondissement:id,libelle')
            ->withCount('parents', 'seances')
            ->get();

        return response()->json([
            'portee' => ['niveau' => $portee->niveau, 'libelle' => $portee->libelle],
            'cohortes' => $cohortes->map(fn (Cohorte $c) => [
                'id' => $c->id,
                'libelle' => $c->libelle,
                'arrondissement' => $c->arrondissement->libelle,
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
            ])->sortBy('arrondissement')->values()->all(),
        ]);
    }
}
