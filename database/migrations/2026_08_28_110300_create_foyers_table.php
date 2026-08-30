<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Un dossier de foyer, SANS identite.
        |
        | Aucun nom, aucun prenom, aucune adresse precise, aucune coordonnee
        | GPS. Une localite, un facilitateur referent, une composition, des
        | difficultes fonctionnelles graduees. Le foyer est rattache au
        | facilitateur qui le suit, il n'est pas identifie en lui-meme.
        |
        | C'est la seule facon d'enregistrer une visite a domicile sans creer un
        | fichier de familles vulnerables — document qui, une fois copie, ne
        | protege plus personne.
        */
        Schema::create('foyers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('facilitateur_id')->constrained()->cascadeOnDelete();
            $table->foreignId('arrondissement_id')->constrained()->cascadeOnDelete();
            // Une localite, jamais une adresse : « quartier Nko'ovos ».
            $table->string('localite');

            $table->unsignedTinyInteger('nb_adultes');
            $table->unsignedTinyInteger('nb_enfants');

            // Les domaines du questionnaire court du Washington Group presents
            // dans le foyer. Une liste, jamais un booleen « handicape » : on
            // enregistre ce qu'on a du mal a faire, pas une etiquette.
            $table->json('difficultes_fonctionnelles_foyer');

            $table->boolean('deja_suivi_programme');

            // Renseigne SI, plus tard, quelqu'un du foyer ouvre un compte. Le
            // lien va du foyer vers le parent, jamais l'inverse : un parent ne
            // doit pas donner acces au dossier de son foyer.
            $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete();

            $table->dateTime('recue_a');
            $table->timestamps();

            $table->index(['arrondissement_id', 'facilitateur_id']);
        });

        Schema::create('visites', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('foyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facilitateur_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Des observations STRUCTUREES : des cases cochees, pas un recit.
            // Un champ libre finirait par contenir des noms.
            $table->json('observations_structurees');
            $table->boolean('suivi_prevu');
            $table->dateTime('recue_a');
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visites');
        Schema::dropIfExists('foyers');
    }
};
