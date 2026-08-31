<?php

use App\Http\Controllers\Api\AnnuaireController;
use App\Http\Controllers\Api\Facilitateur\CohorteController;
use App\Http\Controllers\Api\Facilitateur\EvenementController;
use App\Http\Controllers\Api\Facilitateur\FormationController;
use App\Http\Controllers\Api\Facilitateur\TerrainController;
use App\Http\Controllers\Api\Facilitateur\TableauDeBordController as TableauDeBordFacilitateurController;
use App\Http\Controllers\Api\ParentEspace\AssistantController;
use App\Http\Controllers\Api\ParentEspace\CatalogueController;
use App\Http\Controllers\Api\ParentEspace\FeuilletonController;
use App\Http\Controllers\Api\ParentEspace\QuestionController;
use App\Http\Controllers\Api\LangueController;
use App\Http\Controllers\Api\Minproff\BibliothequeController;
use App\Http\Controllers\Api\Minproff\CampagneController;
use App\Http\Controllers\Api\Minproff\CanalController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\Superviseur\CohorteController as CohorteSuperviseurController;
use App\Http\Controllers\Api\Superviseur\EnregistrementFacilitateurController;
use App\Http\Controllers\Api\Superviseur\ParametreCohorteController;
use App\Http\Controllers\Api\Superviseur\RapportController;
use App\Http\Controllers\Api\Superviseur\RegistreController;
use App\Http\Controllers\Api\Superviseur\SignalementController;
use App\Http\Controllers\Api\Superviseur\TableauDeBordController;
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
// Les langues du programme. Publique parce que le parent choisit la sienne
// AVANT de se connecter : on ne peut pas lui demander de lire « choisissez
// votre langue » dans une langue qu'il n'a pas encore choisie.
Route::get('langues', [LangueController::class, 'index']);

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

        // Le même tableau de bord que celui des délégations, à la cinquième
        // portée : celle d'un facilitateur, c'est-à-dire lui-même.
        Route::get('tableau-de-bord', [TableauDeBordFacilitateurController::class, 'show']);

        /*
         * Le terrain. RIEN ne s'écrit ici : activités, foyers, visites, groupes
         * et signalements passent tous par la file d'événements, parce qu'ils
         * se saisissent sans réseau. Ces routes ne servent qu'à relire.
         */
        /*
         * Ses modules de formation. Un module non validé n'est jamais servi,
         * et la progression remonte par la file : on révise sans réseau.
         */
        Route::get('formation', [FormationController::class, 'index']);
        Route::get('formation/{code}', [FormationController::class, 'show']);

        Route::get('referentiel', [TerrainController::class, 'referentiel']);
        Route::get('activites', [TerrainController::class, 'index']);
        Route::get('foyers', [TerrainController::class, 'foyers']);
        Route::get('groupes-soutien', [TerrainController::class, 'groupes']);

        // La suite donnée à ses signalements. C'est ce qui décide s'il en fera
        // un deuxième.
        Route::get('signalements', [TerrainController::class, 'signalements']);

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
        // Un seul tableau de bord pour les quatre niveaux. `niveau` et
        // `entite` le font descendre, dans les limites de la portée du compte.
        Route::get('tableau-de-bord', [TableauDeBordController::class, 'show']);

        Route::get('facilitateurs', [RegistreController::class, 'index']);

        // L'enregistrement d'un facilitateur. Reserve au niveau arrondissement :
        // c'est le superviseur qui enregistre et remet les identifiants.
        Route::get('types-juridiques', [EnregistrementFacilitateurController::class, 'typesJuridiques']);
        Route::post('facilitateurs', [EnregistrementFacilitateurController::class, 'store']);
        Route::post('facilitateurs/{facilitateur}/identifiants',
            [EnregistrementFacilitateurController::class, 'regenerer']);
        Route::get('cohortes', [CohorteSuperviseurController::class, 'index']);
        Route::get('rapport', [RapportController::class, 'show']);

        // La file des signalements. Aucune autorite n'est notifiee : ils
        // arrivent ici, et c'est un humain qui juge.
        Route::get('signalements', [SignalementController::class, 'index']);
        Route::patch('signalements/{signalement}', [SignalementController::class, 'update']);
        Route::patch('cohortes/{cohorte}', [ParametreCohorteController::class, 'update']);

        /*
         * Le ministère. Ces routes vivent sous le préfixe `superviseur` parce
         * qu'elles partagent son jeton — la chaîne administrative n'a qu'une
         * seule porte d'entrée. C'est la PORTÉE du compte qui les autorise,
         * pas une permission séparée : `niveau === 'national'`.
         */
        Route::get('bibliotheque', [BibliothequeController::class, 'index']);
        Route::patch('bibliotheque/modules/{code}', [BibliothequeController::class, 'valider']);
        Route::post('bibliotheque/langues', [BibliothequeController::class, 'langue']);
        Route::patch('bibliotheque/langues/{id}', [BibliothequeController::class, 'langue']);

        // Les campagnes se LISENT à tous les niveaux, ne se créent qu'au
        // national, et s'accusent en réception à tous les autres.
        Route::get('campagnes', [CampagneController::class, 'index']);
        Route::post('campagnes', [CampagneController::class, 'store']);
        Route::post('campagnes/{campagne}/reception', [CampagneController::class, 'accuser']);

        Route::get('canaux', [CanalController::class, 'index']);
    });

/*
 * Espace parent — secondaire et optionnel.
 * Le jeton expire après quelques minutes et n'est jamais renouvelé
 * silencieusement : le téléphone est souvent partagé au sein du foyer.
 */
/*
 * Lecture : AUCUN compte requis.
 *
 * Les contenus du programme sont produits et validés par le ministère pour
 * etre lus. Exiger un code pour les consulter reviendrait a les reserver aux
 * parents deja inscrits, c'est-a-dire a ceux qui en ont le moins besoin.
 *
 * Ces routes ne rendent RIEN de personnel : des unites validees, des episodes,
 * des questions de la semaine, et un assistant a corpus ferme dont le journal
 * ne porte aucun identifiant. Un jeton reste accepte — il sert alors a servir
 * la langue attachee au dossier du parent.
 */
Route::prefix('parent')->group(function () {
    Route::get('modules', [CatalogueController::class, 'modules']);
    Route::get('modules/{module}/unites', [CatalogueController::class, 'unites']);
    Route::get('unites/{unite}', [CatalogueController::class, 'unite']);

    Route::get('feuilletons', [FeuilletonController::class, 'index']);

    Route::get('questions', [QuestionController::class, 'index']);

    Route::get('situations', [AssistantController::class, 'situations']);
    Route::post('assistant', [AssistantController::class, 'poser'])->middleware('throttle:assistant');
});

/*
 * Ce qui s'attache a une personne exige un compte. Repondre a la question de
 * la semaine ecrit quelque chose au nom d'un parent : cela ne se fait pas
 * anonymement.
 */
Route::middleware(['auth:sanctum', 'abilities:parent'])
    ->prefix('parent')
    ->group(function () {
        Route::post('questions/{question}/reponse', [QuestionController::class, 'repondre']);
    });
