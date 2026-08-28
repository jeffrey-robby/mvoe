<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // L'OBSERVE. Enregistre passivement quand le facilitateur ouvre une sequence
        // pendant la seance : aucune saisie, aucune action volontaire de sa part.
        // Confronte au DECLARE (fiches_fidelite), il produit l'ecart.
        Schema::create('sequences_ouvertes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->dateTime('ouverte_a');
            // Nullable : la sequence peut etre encore ouverte au moment de la remontee.
            $table->unsignedInteger('duree_reelle_secondes')->nullable();
            $table->timestamps();

            // Pas de contrainte d'unicite (seance, sequence) : revenir en arriere
            // sur une sequence est un fait reel, on garde chaque ouverture.
            $table->index(['seance_id', 'sequence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences_ouvertes');
    }
};
