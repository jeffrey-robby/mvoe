<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Enums\TypeJuridique;
use App\Http\Controllers\Controller;
use App\Models\Facilitateur;
use App\Services\EnregistrementFacilitateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Enregistrement d'un facilitateur par son superviseur.
 *
 * Le trou principal du système jusqu'ici : on pouvait se connecter comme
 * facilitateur, mais rien ne disait qui créait ce compte.
 *
 * Noter ce que la requête NE contient PAS : ni arrondissement, ni superviseur,
 * ni mot de passe. Les deux premiers viennent du compte connecté, le troisième
 * est généré. Un superviseur ne peut donc pas créer un facilitateur ailleurs
 * que chez lui, même en forgeant sa requête.
 */
class EnregistrementFacilitateurController extends Controller
{
    public function store(Request $request, EnregistrementFacilitateur $service): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'max:20', Rule::unique('facilitateurs', 'telephone')],
            'email' => ['nullable', 'email', Rule::unique('facilitateurs', 'email')],
            'type_juridique' => ['required', Rule::enum(TypeJuridique::class)],
            'organisation_rattachement' => ['nullable', 'string', 'max:160'],
            'date_formation_initiale' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $resultat = $service->enregistrer($request->user(), $donnees);
        $facilitateur = $resultat['facilitateur'];

        return response()->json([
            'facilitateur' => [
                'id' => $facilitateur->id,
                'nom' => $facilitateur->nom,
                'arrondissement' => $facilitateur->arrondissement->libelle,
                'type_juridique' => $facilitateur->type_juridique->libelle(),
                'date_formation_initiale' => $facilitateur->date_formation_initiale->toDateString(),
            ],

            // AFFICHÉS UNE SEULE FOIS. En base tout est haché : ni cette API ni
            // le superviseur ne pourront les relire. C'est volontaire, et c'est
            // pour cela qu'une régénération existe.
            'identifiants' => $resultat['identifiants'],
            'avertissement' => 'Ces identifiants ne seront plus affichés. Remettez-les au facilitateur avant de fermer cet écran.',
        ], 201);
    }

    /**
     * Régénère les identifiants d'un facilitateur. Ses jetons en cours sont
     * révoqués : un appareil aux anciens identifiants ne doit plus remonter.
     */
    public function regenerer(
        Request $request,
        Facilitateur $facilitateur,
        EnregistrementFacilitateur $service,
    ): JsonResponse {
        return response()->json([
            'identifiants' => $service->regenerer($request->user(), $facilitateur),
            'avertissement' => 'Les anciens identifiants ne fonctionnent plus. Le facilitateur devra rouvrir son kit.',
        ]);
    }

    /** Les types juridiques proposés au formulaire. */
    public function typesJuridiques(): JsonResponse
    {
        return response()->json([
            'types' => collect(TypeJuridique::cases())
                ->map(fn (TypeJuridique $t) => ['valeur' => $t->value, 'libelle' => $t->libelle()])
                ->all(),
        ]);
    }
}
