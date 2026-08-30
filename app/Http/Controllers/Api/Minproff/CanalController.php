<?php

namespace App\Http\Controllers\Api\Minproff;

use App\Canaux\Canaux;
use App\Http\Controllers\Controller;
use App\Models\Diffusion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Les canaux de diffusion.
 *
 * Les pilotes sont FACTICES et l'API le dit (`factice: true` sur chaque
 * canal) : un prototype qui laisserait croire que des SMS partent vraiment
 * mentirait à son jury. Ce qui est réel, c'est l'abstraction — brancher un
 * opérateur consiste à remplacer une ligne du registre des pilotes.
 *
 * Pour la radio, aucune audience n'est rendue. À la place : le surcroît
 * d'appels et de sessions dans les 48 heures suivant une diffusion attestée.
 */
class CanalController extends Controller
{
    public function index(Request $request, Canaux $canaux): JsonResponse
    {
        $portee = $request->user()->portee();

        $du = Carbon::parse($request->query('du', now()->subMonths(6)->toDateString()));
        $au = Carbon::parse($request->query('au', now()->toDateString()))->endOfDay();

        return response()->json([
            'periode' => ['du' => $du->toDateString(), 'au' => $au->toDateString()],
            'portee' => ['niveau' => $portee->niveau, 'libelle' => $portee->libelle],

            // Dit en clair, une fois, plutôt que caché dans une note de bas de
            // page : rien de tout cela n'est branché.
            'pilotes_factices' => true,

            'canaux' => $canaux->statistiques($du, $au),

            'dernieres' => Diffusion::dansLaPortee($portee)
                ->with('langue', 'unite')
                ->whereBetween('date', [$du, $au])
                ->orderByDesc('date')
                ->limit(20)
                ->get()
                ->map(fn (Diffusion $d) => [
                    'canal' => $d->canal->value,
                    'canal_libelle' => $d->canal->libelle(),
                    'date' => $d->date->toDateTimeString(),
                    'cible' => $d->cible,
                    'langue' => $d->langue?->nom(),
                    'volume' => $d->volume,
                    'aboutis' => $d->aboutis,
                    'unite' => $d->canal->unite(),
                    'atteste_par' => $d->atteste_par,
                ])->values()->all(),
        ]);
    }
}
