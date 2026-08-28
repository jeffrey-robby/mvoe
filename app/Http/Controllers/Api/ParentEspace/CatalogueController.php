<?php

namespace App\Http\Controllers\Api\ParentEspace;

use App\Enums\Langue;
use App\Enums\Modalite;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Realisation;
use App\Models\UniteDigitale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Écran « Écouter » : les modules, puis les unités.
 *
 * Chaque unité existe en audio et en texte + pictogrammes, dans la langue
 * demandée : aucun parcours ne dépend de la capacité à lire, et aucun ne
 * s'interrompt si un enregistrement manque.
 */
class CatalogueController extends Controller
{
    public function modules(Request $request): JsonResponse
    {
        $modules = Module::ordonnes()->withCount('unites')->get();

        return response()->json([
            'modules' => $modules->map(fn (Module $m) => [
                'id' => $m->id,
                'numero' => $m->numero,
                'titre' => $m->titre,
                'unites' => $m->unites_count,
                // Les modules encore vides restent visibles : le parent voit
                // l'architecture du programme, sans qu'on lui fasse croire
                // qu'un contenu existe.
                'renseigne' => $m->unites_count > 0,
            ])->all(),
        ]);
    }

    public function unites(Request $request, Module $module): JsonResponse
    {
        $langue = $this->langue($request);

        $unites = $module->unites()->with('realisations', 'sequence')->get();

        return response()->json([
            'module' => ['id' => $module->id, 'numero' => $module->numero, 'titre' => $module->titre],
            'unites' => $unites->map(fn (UniteDigitale $u) => [
                'id' => $u->id,
                'titre' => $u->realisation($langue, Modalite::Audio)?->titre
                    ?? $u->realisation($langue, Modalite::TextePicto)?->titre,
                'sequence' => ['ordre' => $u->sequence->ordre, 'titre' => $u->sequence->titre],
                'audio_disponible' => (bool) $u->realisation($langue, Modalite::Audio)?->aUnAudio(),
            ])->all(),
        ]);
    }

    /**
     * Une unité dans une langue et une modalité. La bascule audio ↔ texte +
     * pictogrammes se fait en changeant `modalite`, sans quitter l'écran.
     */
    public function unite(Request $request, UniteDigitale $unite): JsonResponse
    {
        $donnees = $request->validate([
            'langue' => ['nullable', Rule::enum(Langue::class)],
            'modalite' => ['nullable', Rule::enum(Modalite::class)],
        ]);

        $langue = $this->langue($request);
        $modalite = Modalite::tryFrom($donnees['modalite'] ?? '') ?? Modalite::Audio;

        $unite->load('realisations', 'module', 'sequence');
        $realisation = $unite->realisation($langue, $modalite);

        return response()->json([
            'id' => $unite->id,
            'message_cle' => $unite->message_cle,
            'reference' => $unite->reference(),
            'langue_demandee' => $langue->value,
            // Repli sur le français quand la langue demandée n'existe pas
            // encore : on le dit, on ne le masque pas.
            'langue_servie' => $realisation?->langue->value,
            'modalite' => $modalite->value,
            'realisation' => $realisation ? $this->realisation($realisation) : null,
            'modalites_disponibles' => $unite->realisations
                ->where('langue', $langue)
                ->pluck('modalite')
                ->map(fn (Modalite $m) => $m->value)
                ->values()
                ->all(),
        ]);
    }

    private function realisation(Realisation $realisation): array
    {
        return [
            'titre' => $realisation->titre,
            'contenu_texte' => $realisation->contenu_texte,
            'fichier_audio' => $realisation->fichier_audio ? asset($realisation->fichier_audio) : null,
            'audio_disponible' => $realisation->aUnAudio(),
            'pictogrammes' => $realisation->pictogrammes,
        ];
    }

    private function langue(Request $request): Langue
    {
        return Langue::tryFrom((string) $request->query('langue'))
            ?? $request->user()->langue_pref;
    }
}
