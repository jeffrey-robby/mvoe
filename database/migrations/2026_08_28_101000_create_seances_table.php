<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            // UUID genere par le client hors ligne, avant tout contact avec le serveur.
            // C'est la cle d'idempotence de la remontee.
            $table->uuid('uuid')->unique();
            $table->foreignId('cohorte_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('facilitateur_id')->constrained()->cascadeOnDelete();
            // Horodatage serveur de la reception. `date` moins `recue_a` donne
            // le delai de remontee affiche dans le rapport du superviseur.
            $table->dateTime('recue_a')->nullable();
            $table->timestamps();

            $table->index(['cohorte_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
