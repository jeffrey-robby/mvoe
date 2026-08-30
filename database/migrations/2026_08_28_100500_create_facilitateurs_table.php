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
            $table->foreignId('arrondissement_id')->constrained()->cascadeOnDelete();

            // Le superviseur qui l'a ENREGISTRE et lui a remis ses identifiants.
            // Un facilitateur ne s'inscrit jamais lui-meme : cette colonne rend
            // la chaine d'enregistrement verifiable, et elle est obligatoire.
            $table->foreignId('superviseur_id')->constrained('users')->cascadeOnDelete();

            // Ce qu'il est juridiquement. Ce n'est pas decoratif : c'est ce qui
            // permettra de savoir quel type de facilitateur reste actif le plus
            // longtemps -- une association de femmes tient-elle mieux qu'un
            // vacataire ? Personne ne peut repondre aujourd'hui.
            $table->enum('type_juridique', [
                'agent_public', 'enseignant', 'ong', 'association_femmes',
                'groupe_religieux', 'relais_communautaire', 'vacataire',
            ]);
            $table->string('organisation_rattachement')->nullable();
            $table->date('date_formation_initiale');
            // Nullable : un facilitateur forme mais jamais actif est precisement
            // ce que le registre doit rendre visible.
            $table->date('derniere_activite')->nullable();
            $table->timestamps();

            $table->index('arrondissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilitateurs');
    }
};
