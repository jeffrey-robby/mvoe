<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Langue;
use Illuminate\Http\JsonResponse;

/**
 * Les langues du programme.
 *
 * Publique, et il le faut : le parent choisit sa langue AVANT de se connecter.
 * On ne peut pas lui demander de lire « Choisissez votre langue » dans une
 * langue qu'il n'a pas encore choisie, ni de s'authentifier d'abord pour
 * découvrir ensuite qu'on ne parle pas la sienne.
 *
 * Elle ne révèle rien : la liste des langues dans lesquelles un programme
 * public est disponible est une information publique.
 */
class LangueController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'langues' => Langue::actives()->get()->map(fn (Langue $l) => [
                'code' => $l->code,
                'libelle' => $l->libelle,
                // Le nom de la langue DANS cette langue : c'est lui qu'on
                // affiche, et il est aussi énoncé à voix haute.
                'nom' => $l->nom(),
            ])->all(),
        ]);
    }
}
