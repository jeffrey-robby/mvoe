<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les dix regions du Cameroun.
     *
     * Neuf d'entre elles n'ont ni departement ni arrondissement dans ce
     * prototype : elles existent en libelle seul, pour que l'interface du
     * MINPROFF montre que le systeme est national par construction. `peuplee`
     * dit lesquelles portent reellement des donnees, afin que l'interface ne
     * laisse jamais croire qu'une region vide est une region sans activite.
     */
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->boolean('peuplee')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
