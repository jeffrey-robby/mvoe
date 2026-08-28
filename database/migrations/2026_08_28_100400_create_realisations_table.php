<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites_digitales')->cascadeOnDelete();
            $table->enum('langue', ['fr', 'en', 'bulu']);
            $table->enum('modalite', ['audio', 'texte_picto']);
            // Libelle court dans la langue de la realisation, utilise pour lister les unites.
            $table->string('titre')->nullable();
            $table->text('contenu_texte')->nullable();
            // Nullable : l'interface doit rester utilisable quand l'audio manque.
            $table->string('fichier_audio')->nullable();
            // Noms de pictogrammes accompagnant la modalite texte_picto.
            $table->json('pictogrammes')->nullable();
            $table->timestamps();

            $table->unique(['unite_id', 'langue', 'modalite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisations');
    }
};
