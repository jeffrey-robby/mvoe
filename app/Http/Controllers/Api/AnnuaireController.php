<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facilitateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Écran « Trouver un facilitateur ».
 *
 * AUCUN compte n'est nécessaire : c'est le seul endroit du système où un
 * inconnu obtient quelque chose, et c'est voulu — quelqu'un qui a besoin d'un
 * contact humain ne doit pas d'abord se connecter.
 *
 * L'arrondissement demandé n'est JAMAIS enregistré : pas de journal, pas de
 * compteur, pas de trace. Savoir qu'une personne d'un arrondissement donné a
 * cherché de l'aide est déjà une information de trop.
 */
class AnnuaireController extends Controller
{
    public function arrondissements(): JsonResponse
    {
        return response()->json([
            // Tous les arrondissements du decoupage, pas seulement ceux qui
            // ont deja un facilitateur : quelqu'un doit pouvoir chercher chez
            // lui meme si personne n'y est encore actif.
            'arrondissements' => \App\Models\Arrondissement::orderBy('libelle')->pluck('libelle'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'arrondissement' => ['required', 'string'],
        ]);

        // On ne propose que des facilitateurs joignables : afficher le numéro
        // de quelqu'un qui n'anime plus serait pire que rien.
        $arrondissement = \App\Models\Arrondissement::where('libelle', $donnees['arrondissement'])->first();

        $contacts = Facilitateur::query()
            ->when($arrondissement, fn ($q) => $q->where('arrondissement_id', $arrondissement->id))
            ->when(! $arrondissement, fn ($q) => $q->whereRaw('1 = 0'))
            ->with('arrondissement:id,libelle')
            ->orderBy('nom')
            ->get()
            ->filter->estActif();

        // Cet écran ne doit JAMAIS renvoyer une liste vide. Certains
        // arrondissements n'ont aucun facilitateur actif — c'est justement ce
        // que le registre révèle — et quelqu'un qui cherche de l'aide ne peut
        // pas être renvoyé à rien. On élargit alors au département, en le
        // disant clairement plutôt qu'en faisant passer un contact éloigné
        // pour un contact local.
        $repli = $contacts->isEmpty();

        if ($repli) {
            $contacts = Facilitateur::with('arrondissement:id,libelle')->orderBy('nom')->get()
                ->filter->estActif()
                ->sortBy(fn (Facilitateur $f) => $f->arrondissement->libelle.$f->nom);
        }

        return response()->json([
            'arrondissement' => $donnees['arrondissement'],
            'repli_departement' => $repli,
            'message' => $repli
                ? "Aucun facilitateur n'est actif dans cet arrondissement. Voici les facilitateurs les plus proches."
                : null,
            'contacts' => $contacts->map(fn (Facilitateur $f) => [
                'nom' => $f->nom,
                'telephone' => $f->telephone,
                'arrondissement' => $f->arrondissement->libelle,
            ])->values()->all(),
        ]);
    }
}
