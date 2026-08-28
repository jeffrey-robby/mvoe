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
    */

    'assistant' => [
        'seuil' => (float) env('MVOE_SEUIL_ASSISTANT', 8.0),
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

];
