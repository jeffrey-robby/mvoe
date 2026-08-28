<?php

namespace App\Http\Controllers\Api\ParentEspace;

use App\Enums\Langue;
use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Feuilleton;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le feuilleton.
 *
 * La reprise de lecture — « il reprend au bon endroit » — vit dans le
 * navigateur du parent, pas ici. Le serveur n'a pas à savoir où en est
 * quelqu'un : ce serait un historique de consultation, donc une trace
 * consultable par un autre membre du foyer, et cela ne sert à rien au
 * programme. Aucune position de lecture n'est donc envoyée ni stockée.
 */
class FeuilletonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $langue = Langue::tryFrom((string) $request->query('langue')) ?? $request->user()->langue_pref;

        $feuilletons = Feuilleton::where('langue', $langue)->with('episodes')->get();

        if ($feuilletons->isEmpty()) {
            $feuilletons = Feuilleton::where('langue', Langue::Fr)->with('episodes')->get();
        }

        return response()->json([
            'langue' => $langue->value,
            'feuilletons' => $feuilletons->map(fn (Feuilleton $f) => [
                'id' => $f->id,
                'titre' => $f->titre,
                'langue' => $f->langue->value,
                'resume' => $f->resume,
                'episodes' => $f->episodes->map(fn (Episode $e) => [
                    'id' => $e->id,
                    'numero' => $e->numero,
                    'titre' => $e->titre,
                    'duree_secondes' => $e->duree,
                    'duree_lisible' => $e->dureeLisible(),
                    'fichier_audio' => $e->fichier_audio ? asset($e->fichier_audio) : null,
                    'unite_id' => $e->unite_id,
                ])->all(),
            ])->all(),
        ]);
    }
}
