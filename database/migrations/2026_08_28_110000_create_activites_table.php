<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Tout ce qu'un facilitateur fait sur le terrain.
        |
        | Le programme ne se resume pas aux seances de cohorte : une causerie
        | sous l'arbre, un porte-a-porte, une reunion de groupe de soutien
        | comptent autant. N'enregistrer que les seances reviendrait a effacer
        | la moitie du travail reel, puis a conclure qu'il n'a pas eu lieu.
        |
        | AUCUN NOM de participant. On compte des gens, on ne les nomme pas.
        */
        Schema::create('activites', function (Blueprint $table) {
            $table->id();
            // Genere par le kit hors ligne : c'est lui qui rend la remontee
            // idempotente, exactement comme pour une seance.
            $table->uuid('uuid')->unique();
            $table->foreignId('facilitateur_id')->constrained()->cascadeOnDelete();
            // Porte directement, et non par le facilitateur : une activite a
            // bien lieu quelque part, et un facilitateur peut etre mute.
            $table->foreignId('arrondissement_id')->constrained()->cascadeOnDelete();
            // Renseignee seulement pour une seance de cohorte ou une reunion
            // de GSP : une causerie publique n'appartient a aucune cohorte.
            $table->foreignId('cohorte_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type');
            $table->date('date');
            // Une localite, jamais une adresse : « sous le manguier du marche ».
            $table->string('lieu');
            $table->unsignedSmallInteger('duree_minutes');

            /*
            | Ce que le facilitateur declare avoir touche.
            |
            | La repartition par sexe et le nombre de participants en situation
            | de handicap sont saisis a la main, activite par activite. C'est ce
            | qui rend le critere « handicap » MESURABLE plutot que declaratif :
            | sans cette colonne, on ecrit « le programme est inclusif » dans un
            | rapport et personne ne peut le verifier.
            */
            $table->unsignedSmallInteger('nb_parents_touches');
            $table->unsignedSmallInteger('nb_hommes');
            $table->unsignedSmallInteger('nb_femmes');
            $table->unsignedSmallInteger('nb_participants_handicap');

            $table->text('commentaire')->nullable();
            $table->dateTime('recue_a');
            $table->timestamps();

            $table->index(['arrondissement_id', 'date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activites');
    }
};
