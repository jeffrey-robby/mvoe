<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Assistant a corpus ferme
    |--------------------------------------------------------------------------
    |
    | Aucun modele de langage generatif n'intervient ici. Le texte du parent est
    | compare au `message_cle` des unites digitales ; si le meilleur score reste
    | sous le seuil, l'application refuse et oriente vers un facilitateur.
    |
    | Le seuil est volontairement HAUT. Mieux vaut refuser une question a
    | laquelle on aurait pu repondre que repondre a cote sur un sujet de
    | protection de l'enfance. Ajuste-le ici, jamais dans le code.
    |
    | Le score vaut entre 0 et 1 : « quelle part de la question cette unite
    | couvre-t-elle ». Calibre sur les douze situations frequentes
    | (`php artisan mvoe:assistant`), avec six unites en base :
    |
    |     questions couvertes par le module 8 : 0.333 a 1.000
    |     questions hors corpus               : 0.000 a 0.119
    |
    | 0.30 laisse donc plus du double de marge au-dessus du bruit. Monter a
    | 0.40 fait refuser deux questions couvertes de plus ; descendre sous 0.15
    | fait repondre a des questions de sante ou de violence conjugale, ce qui
    | est exclu.
    |
    */

    'assistant' => [
        'seuil' => (float) env('MVOE_SEUIL_ASSISTANT', 0.30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registre des facilitateurs
    |--------------------------------------------------------------------------
    |
    | Nombre de jours sans activite au-dela duquel un facilitateur est presente
    | comme inactif dans le registre du superviseur. Ce n'est pas une colonne en
    | base : le statut se recalcule a chaque consultation.
    |
    */

    'facilitateur' => [
        'jours_inactivite' => (int) env('MVOE_JOURS_INACTIVITE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Groupes de soutien parental
    |--------------------------------------------------------------------------
    |
    | Nombre de jours sans reunion au-dela duquel un groupe est presente comme
    | inactif. Comme pour le facilitateur, ce n'est pas une colonne en base :
    | un booleen `actif` resterait a `true` pendant des annees sans que personne
    | ne s'en apercoive. Le guide officiel recommande une reunion mensuelle ;
    | 90 jours laisse passer deux mois manques avant d'alerter.
    |
    */

    'gsp' => [
        'jours_sans_reunion' => (int) env('MVOE_JOURS_SANS_REUNION', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Espace parent
    |--------------------------------------------------------------------------
    |
    | Session courte et non renouvelee : le telephone est souvent partage au
    | sein du foyer. Aucun « rester connecte », deconnexion a la fermeture.
    |
    */

    'parent' => [
        'duree_session_minutes' => (int) env('MVOE_SESSION_PARENT_MINUTES', 20),
        'age_minimum' => 18,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audios d'interface
    |--------------------------------------------------------------------------
    |
    | « Tout est audible » : aucun parcours de l'espace parent ne depend de la
    | capacite a lire. Les libelles de l'interface eux-memes sont enonces a voix
    | haute -- a commencer par le choix de la langue, qui vient AVANT tout le
    | reste et ne peut donc pas etre lu dans une langue qu'on n'a pas encore
    | choisie.
    |
    | Ces cles produisent des fichiers `audio/interface/{cle}.wav`, generes muets
    | par `php artisan mvoe:audios-muets` en attendant les vrais enregistrements.
    |
    */

    'audios_interface' => [
        // Le selecteur de langue : chaque option est dite dans sa propre langue.
        'langue-fr', 'langue-en', 'langue-bulu',

        // Les trois grandes cartes de l'accueil, plus le lien vers l'annuaire.
        'accueil-ecouter-fr', 'accueil-ecouter-en', 'accueil-ecouter-bulu',
        'accueil-feuilleton-fr', 'accueil-feuilleton-en', 'accueil-feuilleton-bulu',
        'accueil-question-fr', 'accueil-question-en', 'accueil-question-bulu',
        'accueil-facilitateur-fr', 'accueil-facilitateur-en', 'accueil-facilitateur-bulu',
        'accueil-questions-fr', 'accueil-questions-en', 'accueil-questions-bulu',

        // L'ecran d'entree : ce qu'il faut saisir, et l'aide si l'on est bloque.
        'entree-code-fr', 'entree-code-en', 'entree-code-bulu',
        'entree-aide-fr', 'entree-aide-en', 'entree-aide-bulu',
    ],

];
