<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Http\Controllers\Controller;
use App\Models\Facilitateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le registre des facilitateurs.
 *
 * Il répond à une question à laquelle personne ne sait répondre aujourd'hui :
 * combien de facilitateurs formés sont encore actifs ? Le statut n'est pas une
 * colonne en base, il se recalcule à chaque consultation à partir de la
 * dernière activité — un statut stocké se périmerait en silence.
 */
class RegistreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Une délégation d'arrondissement ne lit que le sien. Le filtre de la
        // requête ne peut que restreindre davantage, jamais élargir.
        $perimetre = $request->user()->arrondissement;

        $facilitateurs = Facilitateur::query()
            ->when($perimetre !== null, fn ($q) => $q->where('arrondissement', $perimetre))
            ->when($request->string('arrondissement')->isNotEmpty(),
                fn ($q) => $q->where('arrondissement', $request->string('arrondissement')))
            ->orderBy('arrondissement')
            ->orderBy('nom')
            ->withCount('seances')
            ->get();

        return response()->json([
            'perimetre' => $perimetre ?? 'Département de la Mvila',
            'synthese' => [
                'formes' => $facilitateurs->count(),
                'actifs' => $facilitateurs->filter->estActif()->count(),
                'inactifs' => $facilitateurs->reject->estActif()->count(),
                'jamais_actifs' => $facilitateurs->whereNull('derniere_activite')->count(),
                'seuil_inactivite_jours' => config('mvoe.facilitateur.jours_inactivite'),
            ],
            'facilitateurs' => $facilitateurs->map(fn (Facilitateur $f) => [
                'id' => $f->id,
                'nom' => $f->nom,
                'telephone' => $f->telephone,
                'arrondissement' => $f->arrondissement,
                'date_formation' => $f->date_formation->toDateString(),
                'derniere_activite' => $f->derniere_activite?->toDateString(),
                'jours_depuis_activite' => $f->joursDepuisActivite(),
                'seances_animees' => $f->seances_count,
                'actif' => $f->estActif(),
            ])->values()->all(),
        ]);
    }
}
