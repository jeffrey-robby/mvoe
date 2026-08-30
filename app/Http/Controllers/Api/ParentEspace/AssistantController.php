<?php

namespace App\Http\Controllers\Api\ParentEspace;

use App\Models\Langue;
use App\Enums\Modalite;
use App\Http\Controllers\Controller;
use App\Models\Facilitateur;
use App\Models\SituationFrequente;
use App\Services\AppariementCorpus;
use App\Services\ResultatAppariement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Écran « Poser une question ».
 *
 * Deux entrées, une seule mécanique : la liste de situations fréquentes et le
 * champ texte libre passent par le MÊME appariement. Les libellés de situations
 * ne sont pas des réponses pré-écrites, et certains ne trouvent rien.
 *
 * Le refus est une fonctionnalité, pas une erreur. Il est renvoyé en 200, avec
 * autant de soin qu'une réponse trouvée : le score, le seuil, une phrase claire
 * et le contact d'un facilitateur joignable.
 */
class AssistantController extends Controller
{
    use ResoutLaLangue;

    public function situations(Request $request): JsonResponse
    {
        $langue = $this->langueDemandee($request);

        $situations = SituationFrequente::where('langue_id', $langue->id)->ordonnees()->get();

        if ($situations->isEmpty()) {
            $situations = SituationFrequente::where('langue_id', Langue::parDefaut()->id)
                ->ordonnees()->get();
        }

        return response()->json([
            'situations' => $situations->map(fn (SituationFrequente $s) => [
                'id' => $s->id,
                'libelle' => $s->libelle,
                'pictogramme' => $s->pictogramme,
                'fichier_audio' => $s->fichier_audio ? asset($s->fichier_audio) : null,
            ])->all(),
        ]);
    }

    public function poser(Request $request, AppariementCorpus $assistant): JsonResponse
    {
        $donnees = $request->validate([
            'texte' => ['required_without:situation_id', 'nullable', 'string', 'max:500'],
            'situation_id' => ['required_without:texte', 'nullable', 'integer'],
            'langue' => ['nullable', 'string'],
        ]);

        $texte = isset($donnees['situation_id'])
            ? SituationFrequente::findOrFail($donnees['situation_id'])->libelle
            : $donnees['texte'];

        $resultat = $assistant->chercher($texte);

        // Journal sans parent_id : il sert à repérer ce que le corpus ne couvre
        // pas encore, jamais à savoir qui a demandé quoi.
        $assistant->journaliser($resultat);

        return response()->json($resultat->trouve()
            ? $this->reponse($resultat, $request)
            : $this->refus($resultat, $request));
    }

    private function reponse(ResultatAppariement $resultat, Request $request): array
    {
        $langue = $this->langueDemandee($request);
        $repli = Langue::parDefaut();
        $unite = $resultat->unite->load('realisations', 'module', 'sequence');

        return [
            'trouve' => true,
            'score' => round($resultat->score, 3),
            'seuil' => $resultat->seuil,
            // Restitué MOT POUR MOT. Aucune phrase n'est composée par la
            // machine : ce texte a été écrit puis validé par le ministère.
            'reponse' => $unite->message_cle,
            'reference' => $unite->reference(),
            'module' => ['numero' => $unite->module->numero, 'titre' => $unite->module->titre],
            'texte' => ($texte = $unite->realisation($langue, Modalite::TextePicto)
                ?? $unite->realisation($repli, Modalite::TextePicto))?->contenu_texte,
            'pictogrammes' => $texte?->pictogrammes,
            'fichier_audio' => ($audio = ($unite->realisation($langue, Modalite::Audio)
                ?? $unite->realisation($repli, Modalite::Audio))?->fichier_audio)
                ? asset($audio)
                : null,
        ];
    }

    private function refus(ResultatAppariement $resultat, Request $request): array
    {
        $arrondissementId = $request->user()->cohorte->arrondissement_id;

        // Un contact humain, jamais un signalement : le système affiche un
        // numéro, il n'alerte personne et ne déclenche aucune procédure.
        $contacts = Facilitateur::where('arrondissement_id', $arrondissementId)
            ->with('arrondissement:id,libelle')
            ->get()
            ->filter->estActif();

        // Un refus sans contact serait une impasse. Si l'arrondissement n'a
        // aucun facilitateur actif, on élargit au département.
        if ($contacts->isEmpty()) {
            $contacts = Facilitateur::with('arrondissement:id,libelle')->get()->filter->estActif();
        }

        return [
            'trouve' => false,
            'score' => round($resultat->score, 3),
            'seuil' => $resultat->seuil,
            'message' => "Je n'ai pas de réponse validée à cette question. Un facilitateur pourra vous aider.",
            'contacts' => $contacts->map(fn (Facilitateur $f) => [
                'nom' => $f->nom,
                'telephone' => $f->telephone,
                'arrondissement' => $f->arrondissement->libelle,
            ])->values()->all(),
        ];
    }
}
