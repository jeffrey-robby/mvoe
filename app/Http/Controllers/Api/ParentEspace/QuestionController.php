<?php

namespace App\Http\Controllers\Api\ParentEspace;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Question;
use App\Models\ReponseAgregee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les questions de la semaine.
 *
 * Aucun score, aucun total, aucun verdict, aucune série à ne pas briser. Le
 * champ `est_attendue` des options existe pour l'analyse du programme et ne
 * sort JAMAIS de cette API : il est masqué sur le modèle, et rien ici ne le
 * sérialise. Après une réponse, l'application dit ce que propose le programme
 * et pourquoi — le même texte quel que soit le choix du parent.
 */
class QuestionController extends Controller
{
    /** Trois questions par semaine, comme à l'écran. */
    private const PAR_SEMAINE = 3;

    public function index(): JsonResponse
    {
        $questions = Question::with('options', 'unite')
            ->orderBy('ordre')
            ->limit(self::PAR_SEMAINE)
            ->get();

        return response()->json([
            'questions' => $questions->map(fn (Question $q) => [
                'id' => $q->id,
                'enonce' => $q->enonce,
                'enonce_audio' => $q->enonce_audio ? asset($q->enonce_audio) : null,

                /*
                | L'explication voyage avec la question, et non avec l'option.
                | Le texte etant le meme quel que soit le choix, le servir des
                | la liste ne revele rien : il n'y a pas de bonne reponse a
                | proteger. C'est ce qui permet a un visiteur sans compte de
                | lire ce que propose le programme, qui est toute la valeur de
                | l'exercice — le clic, lui, n'en a aucune.
                */
                'explication' => $q->explication,
                'reference' => $q->unite?->reference(),
                'options' => $q->options->map(fn (Option $o) => [
                    'id' => $o->id,
                    'libelle' => $o->libelle,
                    'pictogramme' => $o->pictogramme,
                ])->all(),
            ])->all(),
        ]);
    }

    /**
     * Le parent répond. On incrémente un compteur agrégé — on saura combien de
     * parents ont choisi chaque option, jamais lesquels — et on renvoie
     * l'explication du programme.
     */
    public function repondre(Request $request, Question $question): JsonResponse
    {
        $donnees = $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        $option = $question->options()->findOrFail($donnees['option_id']);

        ReponseAgregee::incrementer($question->id, $option->id);

        return response()->json([
            'question_id' => $question->id,
            // Pas de champ « correct », pas de score, pas de total : ni ici,
            // ni ailleurs. Le programme ne fait pas la leçon aux parents.
            'explication' => $question->explication,
            'reference' => $question->unite->reference(),
        ]);
    }
}
