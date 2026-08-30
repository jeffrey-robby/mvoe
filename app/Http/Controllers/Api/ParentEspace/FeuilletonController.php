<?php

namespace App\Http\Controllers\Api\ParentEspace;

use App\Models\Langue;
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
    use ResoutLaLangue;

    public function index(Request $request): JsonResponse
    {
        $langue = $this->langueDemandee($request);

        $feuilletons = Feuilleton::where('langue_id', $langue->id)->with('episodes', 'langue')->get();
        $repli = false;

        // Repli annonce, jamais silencieux.
        if ($feuilletons->isEmpty()) {
            $feuilletons = Feuilleton::where('langue_id', Langue::parDefaut()->id)
                ->with('episodes', 'langue')->get();
            $repli = $feuilletons->isNotEmpty();
        }

        return response()->json([
            'langue_demandee' => $this->langueRendue($langue),
            'langue_de_repli' => $repli,
            // Les langues dans lesquelles un feuilleton existe vraiment.
            'langues_disponibles' => Feuilleton::with('langue')->get()
                ->map(fn (Feuilleton $f) => $f->langue)
                ->filter()
                ->unique('id')
                ->sortBy('ordre')
                ->map(fn (Langue $l) => $this->langueRendue($l))
                ->values()
                ->all(),
            'feuilletons' => $feuilletons->map(fn (Feuilleton $f) => [
                'id' => $f->id,
                'titre' => $f->titre,
                'langue' => $this->langueRendue($f->langue),
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
