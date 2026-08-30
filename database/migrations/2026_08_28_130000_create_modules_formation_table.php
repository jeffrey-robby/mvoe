<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Le catalogue destine au FACILITATEUR, distinct de celui des parents.
        |
        | Un facilitateur forme il y a deux ans ne se refait pas former : il
        | rouvre ses modules. Ce faisant il rouvre l'application, donc il reste
        | actif dans le registre — c'est le seul dispositif de reactivation qui
        | ne coute ni deplacement, ni per diem, ni convocation.
        */
        Schema::create('modules_formation', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('titre');
            $table->string('type');
            // Ce que le facilitateur saura faire apres. Une phrase, pas un
            // programme : c'est ce qui lui dit si ce module repond a sa question.
            $table->text('objectif');
            $table->unsignedSmallInteger('ordre');
            $table->unsignedSmallInteger('duree_minutes');

            /*
            | Un contenu non valide ne peut pas etre diffuse.
            |
            | La regle est dans le code qui sert les modules, pas dans une note :
            | un module mal relu qui atteint cinquante facilitateurs se rattrape
            | mal, et personne ne saura lesquels l'ont lu.
            */
            $table->string('statut_validation')->default('brouillon');
            $table->timestamps();

            $table->index(['type', 'ordre']);
        });

        Schema::create('sections_formation', function (Blueprint $table) {
            $table->id();
            // La table s'appelle `modules_formation`, pas `module_formations` :
            // sans le dire, Laravel devine mal et la contrainte échoue.
            $table->foreignId('module_formation_id')
                ->constrained('modules_formation')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordre');
            $table->string('titre');
            $table->text('contenu_texte');
            // Nullable : l'interface reste utilisable quand l'audio manque.
            $table->string('fichier_audio')->nullable();
            $table->unsignedSmallInteger('duree_minutes');
            $table->timestamps();

            $table->unique(['module_formation_id', 'ordre']);
        });

        /*
        | La progression d'un facilitateur dans un module.
        |
        | Elle est SIENNE et visible par son superviseur : c'est la seule facon
        | de savoir qui a rouvert quoi, et donc de reperer qui decroche avant
        | qu'il ne disparaisse du registre.
        |
        | `sections_vues` est un tableau d'ordres, pas un compteur : reprendre au
        | milieu suppose de savoir OU l'on en etait, et un compteur ne le dit pas
        | quand on saute une section.
        */
        Schema::create('progressions_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facilitateur_id')->constrained()->cascadeOnDelete();
            // La table s'appelle `modules_formation`, pas `module_formations` :
            // sans le dire, Laravel devine mal et la contrainte échoue.
            $table->foreignId('module_formation_id')
                ->constrained('modules_formation')->cascadeOnDelete();
            $table->json('sections_vues');
            $table->dateTime('derniere_ouverture');
            $table->dateTime('termine_a')->nullable();
            $table->timestamps();

            // Nommé à la main : le nom deviné dépasse les 64 caractères
            // qu'accepte MySQL pour un identifiant.
            $table->unique(['facilitateur_id', 'module_formation_id'],
                'progressions_formation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progressions_formation');
        Schema::dropIfExists('sections_formation');
        Schema::dropIfExists('modules_formation');
    }
};
