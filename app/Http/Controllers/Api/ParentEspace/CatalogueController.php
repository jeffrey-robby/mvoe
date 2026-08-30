<?php

namespace App\Http\Controllers\Api\ParentEspace;

use App\Models\Langue;
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
    use ResoutLaLangue;

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
        $langue = $this->langueDemandee($request);
        $repli = Langue::parDefaut();

        $unites = $module->unites()->with('realisations.langue', 'sequence')->get();

        return response()->json([
            'module' => ['id' => $module->id, 'numero' => $module->numero, 'titre' => $module->titre],
            'langue' => $this->langueRendue($langue),

            /*
            | Les langues dans lesquelles CE module existe vraiment.
            |
            | Le selecteur de l'ecran ne propose que celles-ci. Proposer une
            | langue qui n'est pas chargee, c'est promettre un contenu qui
            | n'existe pas, et faire porter au parent la deception d'un
            | catalogue incomplet.
            */
            'langues_disponibles' => $unites
                ->flatMap(fn (UniteDigitale $u) => $u->languesDisponibles())
                ->unique('id')
                ->sortBy('ordre')
                ->map(fn (Langue $l) => $this->langueRendue($l))
                ->values()
                ->all(),

            'unites' => $unites->map(function (UniteDigitale $u) use ($langue, $repli) {
                $servie = $u->realisation($langue, Modalite::Audio)
                    ?? $u->realisation($langue, Modalite::TextePicto)
                    ?? $u->realisation($repli, Modalite::Audio)
                    ?? $u->realisation($repli, Modalite::TextePicto);

                return [
                    'id' => $u->id,
                    'titre' => $servie?->titre,
                    'sequence' => ['ordre' => $u->sequence->ordre, 'titre' => $u->sequence->titre],
                    'audio_disponible' => (bool) $u->realisation($langue, Modalite::Audio)?->aUnAudio(),
                    // Dit, jamais masque : le parent sait dans quelle langue il
                    // s'apprete a ecouter.
                    'langue_servie' => $this->langueRendue($servie?->langue),
                ];
            })->all(),
        ]);
    }

    /**
     * Une unité dans une langue et une modalité. La bascule audio ↔ texte +
     * pictogrammes se fait en changeant `modalite`, sans quitter l'écran.
     */
    public function unite(Request $request, UniteDigitale $unite): JsonResponse
    {
        $donnees = $request->validate([
            'langue' => ['nullable', 'string'],
            'modalite' => ['nullable', Rule::enum(Modalite::class)],
        ]);

        $langue = $this->langueDemandee($request);
        $modalite = Modalite::tryFrom($donnees['modalite'] ?? '') ?? Modalite::Audio;

        $unite->load('realisations.langue', 'module', 'sequence');

        // Repli sur la langue par defaut quand la langue demandee n'est pas
        // chargee sur cette unite. Le repli est TOUJOURS annonce.
        $realisation = $unite->realisation($langue, $modalite)
            ?? $unite->realisation(Langue::parDefaut(), $modalite);

        return response()->json([
            'id' => $unite->id,
            'message_cle' => $unite->message_cle,
            'reference' => $unite->reference(),
            'langue_demandee' => $this->langueRendue($langue),
            'langue_servie' => $this->langueRendue($realisation?->langue),
            // L'ecran s'en sert pour dire « disponible en francais seulement »
            // plutot que de faire croire a une traduction qui n'existe pas.
            'langue_de_repli' => $realisation !== null
                && $realisation->langue_id !== $langue->id,
            'langues_disponibles' => $unite->languesDisponibles()
                ->map(fn (Langue $l) => $this->langueRendue($l))
                ->all(),
            'modalite' => $modalite->value,
            'realisation' => $realisation ? $this->realisation($realisation) : null,
            'modalites_disponibles' => $unite->realisations
                ->where('langue_id', $realisation?->langue_id)
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

}
