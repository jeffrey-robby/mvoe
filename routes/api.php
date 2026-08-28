<?php

use App\Http\Controllers\Api\AnnuaireController;
use App\Http\Controllers\Api\Facilitateur\CohorteController;
use App\Http\Controllers\Api\Facilitateur\EvenementController;
use App\Http\Controllers\Api\ParentEspace\AssistantController;
use App\Http\Controllers\Api\ParentEspace\CatalogueController;
use App\Http\Controllers\Api\ParentEspace\FeuilletonController;
use App\Http\Controllers\Api\ParentEspace\QuestionController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\Superviseur\CohorteController as CohorteSuperviseurController;
use App\Http\Controllers\Api\Superviseur\ParametreCohorteController;
use App\Http\Controllers\Api\Superviseur\RapportController;
use App\Http\Controllers\Api\Superviseur\RegistreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Mvoé
|--------------------------------------------------------------------------
|
| C'est la seule porte d'entrée des données. Le client Blade la consomme
| exactement comme le fera l'application Flutter : aucun privilège n'est
| attaché au navigateur, aucune route web ne double une route d'ici.
|
| Trois permissions de jeton, strictement cloisonnées : `facilitateur`,
| `parent`, `superviseur`. Un jeton parent ne peut pas lire une cohorte, un
| jeton facilitateur ne peut pas lire le registre.
|
*/

/*
 * Public — aucun compte requis.
 * « Trouver un facilitateur » doit rester accessible à quelqu'un qui n'a pas
 * de code, qui l'a perdu, ou qui n'est pas inscrit au programme.
 */
Route::get('arrondissements', [AnnuaireController::class, 'arrondissements']);
Route::get('annuaire', [AnnuaireController::class, 'index']);

/*
 * Ouverture de session. Limitée en débit : les codes du système sont courts
 * par nécessité, c'est la limite d'essais qui les protège.
 */
Route::middleware('throttle:connexion')->group(function () {
    Route::post('facilitateur/session', [SessionController::class, 'facilitateur']);
    Route::post('parent/session', [SessionController::class, 'parent']);
    Route::post('superviseur/session', [SessionController::class, 'superviseur']);
});

Route::delete('session', [SessionController::class, 'destroy'])->middleware('auth:sanctum');

/*
 * Kit facilitateur.
 */
Route::middleware(['auth:sanctum', 'abilities:facilitateur'])
    ->prefix('facilitateur')
    ->group(function () {
        Route::get('cohortes', [CohorteController::class, 'index']);
        Route::get('cohortes/{cohorte}/paquet', [CohorteController::class, 'paquet']);

        // La remontée : des événements, jamais des états.
        Route::post('evenements', [EvenementController::class, 'store']);
        Route::get('seances/{uuid}', [EvenementController::class, 'show']);
    });

/*
 * Superviseur — délégation d'arrondissement.
 */
Route::middleware(['auth:sanctum', 'abilities:superviseur'])
    ->prefix('superviseur')
    ->group(function () {
        Route::get('facilitateurs', [RegistreController::class, 'index']);
        Route::get('cohortes', [CohorteSuperviseurController::class, 'index']);
        Route::get('rapport', [RapportController::class, 'show']);
        Route::patch('cohortes/{cohorte}', [ParametreCohorteController::class, 'update']);
    });

/*
 * Espace parent — secondaire et optionnel.
 * Le jeton expire après quelques minutes et n'est jamais renouvelé
 * silencieusement : le téléphone est souvent partagé au sein du foyer.
 */
Route::middleware(['auth:sanctum', 'abilities:parent'])
    ->prefix('parent')
    ->group(function () {
        Route::get('modules', [CatalogueController::class, 'modules']);
        Route::get('modules/{module}/unites', [CatalogueController::class, 'unites']);
        Route::get('unites/{unite}', [CatalogueController::class, 'unite']);

        Route::get('feuilletons', [FeuilletonController::class, 'index']);

        Route::get('questions', [QuestionController::class, 'index']);
        Route::post('questions/{question}/reponse', [QuestionController::class, 'repondre']);

        Route::get('situations', [AssistantController::class, 'situations']);
        Route::post('assistant', [AssistantController::class, 'poser'])->middleware('throttle:assistant');
    });
