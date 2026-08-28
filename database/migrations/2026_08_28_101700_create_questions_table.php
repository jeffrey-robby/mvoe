<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites_digitales')->cascadeOnDelete();
            // Le TEXTE de la question vit dans `questions_traduites`, une ligne
            // par langue -- meme separation que unites_digitales / realisations.
            //
            // La structure reste ici, et elle est commune a toutes les langues :
            // c'est ce qui permet aux compteurs agreges de rester comparables.
            // Si chaque langue avait ses propres questions, on ne saurait plus
            // combien de parents ont choisi une option, seulement combien l'ont
            // choisie EN FRANCAIS.
            $table->unsignedTinyInteger('ordre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
