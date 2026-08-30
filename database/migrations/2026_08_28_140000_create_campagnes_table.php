<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Une campagne : le ministere pousse des modules dans des langues, sur
        | des territoires, entre deux dates.
        |
        | Le brief est explicite : PAS de logique metier de propagation
        | complexe. On cree les enregistrements d'affectation et on affiche
        | l'etat d'avancement. Le prototype montre le schema de la cascade
        | administrative, il ne simule pas l'administration.
        */
        Schema::create('campagnes', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('objet')->nullable();
            // Les modules et les langues visees. Des listes d'identifiants
            // plutot que des tables de liaison : une campagne ne se requete
            // jamais par module, seulement par territoire.
            $table->json('module_ids');
            $table->json('langue_ids');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut')->default('brouillon');
            $table->foreignId('creee_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /*
        | La cascade, sous forme d'enregistrements.
        |
        | Une ligne par entite touchee, a chaque niveau : region, departement,
        | arrondissement, facilitateur. Elles sont creees TOUTES EN MEME TEMPS
        | au declenchement — la descente n'est pas un processus asynchrone, elle
        | est un fait administratif qu'on enregistre.
        |
        | `entite_id` n'a pas de contrainte : il designe une region, un
        | departement, un arrondissement ou un facilitateur selon `niveau`.
        | Une cle etrangere polymorphe couterait plus qu'elle ne protegerait
        | dans un prototype ou ces quatre tables ne sont jamais supprimees.
        */
        Schema::create('campagne_affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campagne_id')->constrained()->cascadeOnDelete();
            $table->string('niveau');
            $table->unsignedBigInteger('entite_id');
            $table->string('statut')->default('affectee');
            // Quand l'echelon a pris connaissance. Nullable : la plupart ne
            // l'ont pas encore fait, et c'est precisement ce que l'ecran montre.
            $table->dateTime('date_reception')->nullable();
            $table->timestamps();

            $table->unique(['campagne_id', 'niveau', 'entite_id'], 'campagne_affectation_unique');
            $table->index(['niveau', 'entite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagne_affectations');
        Schema::dropIfExists('campagnes');
    }
};
