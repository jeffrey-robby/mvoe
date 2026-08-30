<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Http\Controllers\Controller;
use App\Services\RapportTrimestriel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    /**
     * Le rapport trimestriel. C'est un DOCUMENT : une photographie d'un
     * trimestre clos, pas un écran qui se rafraîchit.
     */
    public function show(Request $request, RapportTrimestriel $rapport): JsonResponse
    {
        $donnees = $request->validate([
            'annee' => ['required', 'integer', 'min:2020', 'max:2100'],
            'trimestre' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        return response()->json($rapport->pour(
            $donnees['annee'],
            $donnees['trimestre'],
            // La portée vient du compte, jamais de la requête : on ne peut pas
            // élargir sa lecture en changeant un paramètre d'URL.
            $request->user()->portee(),
        ));
    }
}
