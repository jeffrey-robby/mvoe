<?php

namespace App\Http\Controllers\Api\Facilitateur;

use App\Http\Controllers\Controller;
use App\Models\ModuleFormation;
use App\Models\ProgressionFormation;
use App\Models\SectionFormation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les modules de formation du facilitateur.
 *
 * Rien ne s'écrit ici : la progression remonte par la file d'événements, parce
 * qu'un facilitateur révise dans un car, sur un banc, sans réseau. Ce
 * contrôleur ne fait que servir le catalogue et rappeler où il en était.
 *
 * **Un module non validé n'est jamais servi.** La règle vit dans le scope
 * `diffusables()` et nulle part ailleurs : un module mal relu qui atteint
 * cinquante facilitateurs se rattrape mal, et personne ne saura lesquels
 * l'ont lu.
 */
class FormationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $facilitateur = $request->user();

        $modules = ModuleFormation::diffusables()->withCount('sections')->get();

        $progressions = ProgressionFormation::where('facilitateur_id', $facilitateur->id)
            ->get()
            ->keyBy('module_formation_id');

        return response()->json([
            'modules' => $modules->map(function (ModuleFormation $m) use ($progressions) {
                $p = $progressions->get($m->id);

                return [
                    'code' => $m->code,
                    'titre' => $m->titre,
                    'type' => $m->type->value,
                    'type_libelle' => $m->type->libelle(),
                    'type_description' => $m->type->description(),
                    'objectif' => $m->objectif,
                    'duree_minutes' => $m->duree_minutes,
                    'sections' => $m->sections_count,
                    // Ce qu'il a déjà vu, pour reprendre où il en était plutôt
                    // que de tout recommencer.
                    'sections_vues' => $p?->sections_vues ?? [],
                    'avancement' => $p?->avancement($m->sections_count) ?? 0,
                    'termine' => (bool) $p?->estTermine(),
                    'derniere_ouverture' => $p?->derniere_ouverture?->toDateString(),
                ];
            })->values()->all(),
        ]);
    }

    /** Un module et ses sections, servi seulement s'il est validé. */
    public function show(Request $request, string $code): JsonResponse
    {
        $module = ModuleFormation::diffusables()->where('code', $code)->firstOrFail();

        $progression = ProgressionFormation::where('facilitateur_id', $request->user()->id)
            ->where('module_formation_id', $module->id)
            ->first();

        return response()->json([
            'code' => $module->code,
            'titre' => $module->titre,
            'type_libelle' => $module->type->libelle(),
            'objectif' => $module->objectif,
            'duree_minutes' => $module->duree_minutes,
            'sections_vues' => $progression?->sections_vues ?? [],
            'sections' => $module->sections->map(fn (SectionFormation $s) => [
                'ordre' => $s->ordre,
                'titre' => $s->titre,
                'contenu_texte' => $s->contenu_texte,
                'duree_minutes' => $s->duree_minutes,
                'fichier_audio' => $s->fichier_audio ? asset($s->fichier_audio) : null,
                'audio_disponible' => $s->aUnAudio(),
            ])->values()->all(),
        ]);
    }
}
