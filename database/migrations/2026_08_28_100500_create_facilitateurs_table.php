<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SEULES personnes nominatives du systeme. Ce sont des agents publics :
        // leur nom et leur telephone sont l'annuaire que le parent consulte.
        Schema::create('facilitateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->unique();
            // Deux voies d'entree, pour deux usages reels :
            //
            //  - sur le terrain, il ouvre son KIT avec son numero et un code
            //    d'appareil a 6 chiffres remis en main propre a la formation.
            //    Court, memorisable, saisissable d'une main en plein soleil ;
            //  - depuis un poste de la delegation, il se connecte avec un
            //    e-mail et un mot de passe classiques.
            //
            // Les deux sont haches. Les deux ouvrent la meme session, avec
            // exactement les memes droits : la voie d'entree ne donne aucun
            // privilege supplementaire.
            $table->string('code_appareil');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('arrondissement');
            $table->date('date_formation');
            // Nullable : un facilitateur forme mais jamais actif est precisement
            // ce que le registre doit rendre visible.
            $table->date('derniere_activite')->nullable();
            $table->timestamps();

            $table->index('arrondissement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilitateurs');
    }
};
