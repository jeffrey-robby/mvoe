<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->unsignedTinyInteger('ordre');
            // Duree officielle du guide. C'est elle qui donne sa hauteur a la sequence
            // dans la colonne de seance : la colonne est une echelle, pas une liste.
            $table->unsignedSmallInteger('duree_minutes');
            $table->enum('type', ['unite_digitale', 'consigne_animation']);
            // Texte affiche pour une consigne d'animation (une seule ligne pour le brise-glace).
            $table->text('consigne')->nullable();
            // Le brise-glace est rendu differemment : bande pleine, aucun controle,
            // aucun chronometre. L'interface a besoin d'un drapeau explicite,
            // elle ne doit pas le deviner en lisant le titre.
            $table->boolean('est_brise_glace')->default(false);
            $table->timestamps();

            $table->unique(['module_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
