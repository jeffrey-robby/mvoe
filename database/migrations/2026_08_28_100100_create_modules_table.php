<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->string('titre');
            // `ordre` est un attribut d'affichage. On ne cible JAMAIS un module par son ordre :
            // les liens et l'API passent par l'id, les libelles metier par `numero`.
            $table->unsignedTinyInteger('ordre');
            $table->timestamps();

            $table->unique(['curriculum_version_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
