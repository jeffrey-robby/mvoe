<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Les signalements remontent, ils ne se declenchent jamais.
        |
        | Trois regles absolues, et elles sont dans le schema, pas dans une note :
        |
        |  1. AUCUNE identite d'enfant, de parent ou de foyer. Il n'y a pas de
        |     colonne ou en mettre une, et il ne doit jamais y en avoir. Type,
        |     gravite, arrondissement, rien de plus.
        |  2. Le systeme ne notifie JAMAIS une autorite automatiquement. Le
        |     signalement entre dans la file du superviseur, qui juge et decide.
        |     Une alerte automatique ferait courir un risque a l'enfant qu'elle
        |     pretend proteger.
        |  3. Le facilitateur voit TOUJOURS la suite donnee. Un signalement sans
        |     retour est un signalement qu'on ne refait pas.
        */
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Le contexte d'ou la situation a ete vue, jamais qui elle concerne.
            $table->foreignId('activite_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facilitateur_id')->constrained()->cascadeOnDelete();
            $table->foreignId('arrondissement_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('gravite');
            $table->string('statut')->default('soumis');

            // Le traitement, cote superviseur.
            $table->foreignId('traite_par_superviseur_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->dateTime('date_traitement')->nullable();
            // Ce que le facilitateur lira. C'est la seule raison pour laquelle
            // il en fera un deuxieme.
            $table->text('suite_donnee')->nullable();

            $table->dateTime('recue_a');
            $table->timestamps();

            $table->index(['arrondissement_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
