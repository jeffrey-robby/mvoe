<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Les langues sont des DONNEES, plus jamais du code.
        |
        | Le Cameroun compte plus de deux cents langues. Un enum PHP en fige
        | trois et exige un deploiement pour en ajouter une quatrieme : c'est
        | l'equipe technique qui deciderait alors dans quelle langue un parent
        | peut ecouter le programme. Cette decision appartient au ministere, et
        | elle se prend en chargeant des realisations, pas en modifiant du code.
        |
        | `actif` permet de retirer une langue de l'interface sans supprimer les
        | contenus deja charges : on cesse de la proposer, on ne perd rien.
        */
        Schema::create('langues', function (Blueprint $table) {
            $table->id();
            // Code ISO 639 quand il existe. `bulu` n'a pas de code a deux
            // lettres : la colonne est donc une chaine, pas un enum.
            $table->string('code')->unique();
            $table->string('libelle');
            // Le nom de la langue DANS cette langue. C'est lui qu'on affiche
            // dans le selecteur : personne ne cherche « Bulu » ecrit en
            // francais quand il ne lit pas le francais.
            $table->string('endonyme')->nullable();
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langues');
    }
};
