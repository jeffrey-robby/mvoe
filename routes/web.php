<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Le kit facilitateur
|--------------------------------------------------------------------------
|
| Ces routes ne servent que des COQUILLES. Aucune donnee n'est rendue cote
| serveur : le kit lit le paquet stocke en local et parle a l'API avec un
| jeton, exactement comme le fera l'application Flutter. Le client Blade n'a
| donc aucun privilege que Flutter n'aurait pas -- et c'est aussi ce qui rend
| le fonctionnement hors ligne possible.
|
*/
Route::view('/kit', 'kit.accueil')->name('kit.accueil');
Route::view('/kit/connexion', 'kit.connexion')->name('kit.connexion');
Route::view('/kit/seance', 'kit.seance')->name('kit.seance');
Route::view('/kit/pointage', 'kit.pointage')->name('kit.pointage');
Route::view('/kit/inscrire', 'kit.inscrire')->name('kit.inscrire');
Route::view('/kit/tableau-de-bord', 'kit.tableau-de-bord')->name('kit.tableau-de-bord');
Route::view('/kit/activite', 'kit.activite')->name('kit.activite');
Route::view('/kit/visite', 'kit.visite')->name('kit.visite');
Route::view('/kit/signaler', 'kit.signaler')->name('kit.signaler');
Route::view('/kit/formation', 'kit.formation')->name('kit.formation');
Route::view('/kit/fidelite', 'kit.fidelite')->name('kit.fidelite');

/*
|--------------------------------------------------------------------------
| L'espace parent
|--------------------------------------------------------------------------
|
| Espace SECONDAIRE et optionnel : la majorite des parents du programme n'y
| accedera jamais, et sera servie par la seance, le binome et la radio.
|
| `/parent/facilitateur` est volontairement accessible SANS COMPTE : quelqu'un
| qui a besoin d'un contact humain ne doit pas d'abord se connecter.
|
*/
Route::view('/parent', 'parent.entree')->name('parent.entree');
Route::view('/parent/accueil', 'parent.accueil')->name('parent.accueil');
Route::view('/parent/ecouter', 'parent.ecouter')->name('parent.ecouter');
Route::view('/parent/feuilleton', 'parent.feuilleton')->name('parent.feuilleton');
Route::view('/parent/question', 'parent.question')->name('parent.question');
Route::view('/parent/questions', 'parent.questions')->name('parent.questions');
Route::view('/parent/facilitateur', 'parent.facilitateur')->name('parent.facilitateur');

/*
|--------------------------------------------------------------------------
| La delegation d'arrondissement
|--------------------------------------------------------------------------
|
| Memes coquilles vides que le kit : les donnees viennent de l'API avec un
| jeton. Ces ecrans ne fonctionnent PAS hors ligne, et c'est voulu -- le
| livrable du superviseur est un document trimestriel, pas un outil de terrain.
|
*/
Route::view('/superviseur', 'superviseur.registre')->name('superviseur.registre');
Route::view('/superviseur/tableau-de-bord', 'superviseur.tableau-de-bord')
    ->name('superviseur.tableau-de-bord');
Route::view('/superviseur/connexion', 'superviseur.connexion')->name('superviseur.connexion');
Route::view('/superviseur/enregistrer', 'superviseur.enregistrer')->name('superviseur.enregistrer');
Route::view('/superviseur/signalements', 'superviseur.signalements')
    ->name('superviseur.signalements');
Route::view('/superviseur/campagnes', 'superviseur.campagnes')
    ->name('superviseur.campagnes');
Route::view('/superviseur/bibliotheque', 'superviseur.bibliotheque')
    ->name('superviseur.bibliotheque');
Route::view('/superviseur/contenus', 'superviseur.contenus')
    ->name('superviseur.contenus');
Route::view('/superviseur/canaux', 'superviseur.canaux')->name('superviseur.canaux');
Route::view('/superviseur/rapport', 'superviseur.rapport')->name('superviseur.rapport');
Route::view('/superviseur/parametres', 'superviseur.parametres')->name('superviseur.parametres');

/*
| Page de demonstration du systeme de design. Elle n'est pas un ecran du
| produit : elle sert a verifier tokens, typographie et composants au meme
| endroit.
*/
Route::view('/design', 'design')->name('design');

Route::get('/', fn () => redirect()->route('kit.accueil'));
