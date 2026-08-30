<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Http\Controllers\Controller;
use App\Models\Arrondissement;
use App\Models\Departement;
use App\Models\Region;
use App\Services\TableauDeBord;
use App\Support\Portee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le tableau de bord, pour les quatre niveaux de l'administration.
 *
 * Un seul écran, une seule route. Sans paramètre, il rend le tableau de bord de
 * la portée du compte. Avec `niveau` et `entite`, il descend — et c'est là que
 * se joue le cloisonnement : la portée demandée doit être CONTENUE dans celle
 * du compte. La vérification ne raisonne pas sur la hiérarchie, elle compare
 * deux listes d'arrondissements. Un délégué de la Mvila qui demande l'Océan
 * obtient un 403, quelle que soit la façon dont il formule sa requête.
 */
class TableauDeBordController extends Controller
{
    public function show(Request $request, TableauDeBord $tableau): JsonResponse
    {
        $compte = $request->user()->portee();
        $portee = $this->porteeDemandee($request, $compte);

        return response()->json([
            ...$tableau->pour($portee),
            'fil' => $this->fil($compte, $portee),
        ]);
    }

    /**
     * La portée à lire : celle du compte, ou celle qu'il demande si elle est
     * dans la sienne.
     */
    private function porteeDemandee(Request $request, Portee $compte): Portee
    {
        $valide = $request->validate([
            'niveau' => ['nullable', 'in:region,departement,arrondissement'],
            'entite' => ['nullable', 'integer', 'required_with:niveau'],
        ]);

        if (empty($valide['niveau'])) {
            return $compte;
        }

        $demandee = match ($valide['niveau']) {
            'region' => Portee::region(Region::findOrFail($valide['entite'])),
            'departement' => Portee::departement(Departement::findOrFail($valide['entite'])),
            'arrondissement' => Portee::arrondissement(Arrondissement::findOrFail($valide['entite'])),
        };

        abort_unless($compte->contient($demandee), 403,
            'Cette entité n\'est pas dans votre portée.');

        return $demandee;
    }

    /**
     * Le fil d'Ariane, relatif au compte.
     *
     * Il commence toujours à la portée du compte — au-dessus, il n'a rien à
     * voir, et lui proposer un lien vers le national serait lui montrer une
     * porte fermée. Le premier maillon ramène à son propre tableau de bord.
     *
     * @return array<int, array{niveau: ?string, entite: ?int, libelle: string}>
     */
    private function fil(Portee $compte, Portee $portee): array
    {
        $fil = [['niveau' => null, 'entite' => null, 'libelle' => $compte->libelle]];

        $echelons = [
            ['national', 'region', $portee->regionId, Region::class],
            ['region', 'departement', $portee->departementId, Departement::class],
            ['departement', 'arrondissement', $portee->arrondissementId, Arrondissement::class],
        ];

        $atteint = false;

        foreach ($echelons as [$dessus, $niveau, $id, $modele]) {
            // On ne commence à empiler qu'une fois passé le niveau du compte.
            $atteint = $atteint || $compte->niveau === $dessus;

            if (! $atteint || $id === null) {
                continue;
            }

            $entite = $modele::find($id);

            $fil[] = ['niveau' => $niveau, 'entite' => $id,
                'libelle' => $entite?->libelle ?? ''];
        }

        return $fil;
    }
}
