<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facilitateur;
use App\Models\ParentProgramme;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Ouverture et fermeture de session, pour les trois rôles.
 *
 * Trois entrées distinctes parce que les trois rôles n'ont rien en commun :
 *
 *   - le facilitateur ouvre un KIT sur un téléphone, avec son numéro et un
 *     code d'appareil remis à la formation ;
 *   - le parent entre un code parent et un code à 4 chiffres reçus en main
 *     propre, sans e-mail ni mot de passe, sans SMS de vérification ;
 *   - le superviseur est le seul à avoir un compte classique.
 *
 * Le jeton renvoyé porte une permission (`abilities`) qui borne strictement ce
 * que le porteur peut faire. Le client Blade et la future application Flutter
 * reçoivent exactement le même jeton : aucun privilège n'est attaché au
 * navigateur.
 */
class SessionController extends Controller
{
    /**
     * Deux couples d'identifiants ouvrent la même session, avec exactement les
     * mêmes droits :
     *
     *   - `telephone` + `code_appareil` : le kit, sur le terrain ;
     *   - `email` + `password` : un poste de la délégation.
     *
     * La voie d'entrée ne donne aucun privilège supplémentaire, et le jeton
     * délivré est identique dans les deux cas.
     */
    public function facilitateur(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'telephone' => ['required_without:email', 'nullable', 'string'],
            'code_appareil' => ['required_with:telephone', 'nullable', 'string'],
            'email' => ['required_without:telephone', 'nullable', 'email'],
            'password' => ['required_with:email', 'nullable', 'string'],
        ]);

        [$facilitateur, $champ, $secret, $hache] = filled($donnees['telephone'] ?? null)
            ? [Facilitateur::where('telephone', $donnees['telephone'])->first(),
                'telephone', $donnees['code_appareil'], 'code_appareil']
            : [Facilitateur::where('email', $donnees['email'])->first(),
                'email', $donnees['password'], 'password'];

        if (! $facilitateur || ! Hash::check($secret, $facilitateur->{$hache})) {
            // Le message ne dit pas lequel des deux est faux : inutile
            // d'indiquer à qui essaie qu'un numéro existe bien.
            throw ValidationException::withMessages([
                $champ => 'Ces identifiants ne correspondent pas.',
            ]);
        }

        // Pas d'expiration : le kit doit rester ouvert des jours entiers sans
        // réseau. C'est l'appareil qui est de confiance, pas la session.
        $jeton = $facilitateur->createToken('kit-facilitateur', ['facilitateur']);

        return response()->json([
            'jeton' => $jeton->plainTextToken,
            'expire_a' => null,
            'facilitateur' => [
                'id' => $facilitateur->id,
                'nom' => $facilitateur->nom,
                'arrondissement' => $facilitateur->arrondissement->libelle,
            ],
        ]);
    }

    public function parent(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_parent' => ['required', 'string'],
            'code_acces' => ['required', 'string'],
            // Règle 7 : accès interdit aux moins de 18 ans. La loi exige le
            // consentement du représentant légal, que ce canal ne permet pas
            // de recueillir. Le parent mineur est orienté vers son facilitateur.
            'majeur' => ['required', 'boolean'],
        ]);

        if ($donnees['majeur'] !== true) {
            return response()->json([
                'message' => "L'accès est réservé aux personnes majeures. Rapprochez-vous de votre facilitateur.",
                'orientation' => 'facilitateur',
            ], 403);
        }

        $parent = ParentProgramme::where('code_parent', $donnees['code_parent'])->first();

        if (! $parent || ! Hash::check($donnees['code_acces'], $parent->code_acces)) {
            throw ValidationException::withMessages([
                'code_parent' => 'Ce code parent et ce code à 4 chiffres ne correspondent pas.',
            ]);
        }

        // Session courte, et jamais prolongée : le téléphone est souvent
        // partagé au sein du foyer. Pas de « rester connecté », pas de
        // renouvellement silencieux. Le client garde ce jeton en
        // sessionStorage, jamais en localStorage.
        $expiration = Carbon::now()->addMinutes(config('mvoe.parent.duree_session_minutes'));

        $jeton = $parent->createToken('espace-parent', ['parent'], $expiration);

        return response()->json([
            'jeton' => $jeton->plainTextToken,
            'expire_a' => $expiration->toIso8601String(),
            'parent' => [
                'code_parent' => $parent->code_parent,
                // La langue du parent, pas celle de sa région.
                'langue' => $parent->langue?->code,
            ],
        ]);
    }

    public function superviseur(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $donnees['email'])->first();

        if (! $user || ! Hash::check($donnees['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Ces identifiants ne correspondent pas.',
            ]);
        }

        return response()->json([
            'jeton' => $user->createToken('superviseur', ['superviseur'])->plainTextToken,
            'expire_a' => null,
            'superviseur' => [
                'nom' => $user->name,
                // La portée dit à quel niveau ce compte lit, et ce qu'il
                // couvre. L'écran l'affiche en clair : personne ne doit croire
                // lire tout un département alors qu'il lit un arrondissement.
                'niveau' => $user->niveau,
                'portee' => $user->portee()->libelle,
            ],
        ]);
    }

    /**
     * Déconnexion. Le bouton de sortie est visible sur chaque écran de
     * l'espace parent : c'est la règle 3.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Session fermée.']);
    }
}
