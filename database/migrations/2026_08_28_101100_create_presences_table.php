<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->enum('statut', ['present', 'absent', 'rattrape_binome']);
            $table->timestamps();

            // Un seul etat courant par parent et par seance. L'historique complet
            // des corrections reste dans evenements_sync : rien n'est perdu.
            $table->unique(['seance_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
