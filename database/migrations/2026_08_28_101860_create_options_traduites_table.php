<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le libelle d'une option, dans une langue.
     *
     * L'option elle-meme reste unique et commune a toutes les langues : c'est
     * elle que compte `reponses_agregees`. On sait donc combien de parents ont
     * choisi une reponse, quelle que soit la langue dans laquelle ils l'ont lue.
     */
    public function up(): void
    {
        Schema::create('options_traduites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->enum('langue', ['fr', 'en', 'bulu']);
            $table->string('libelle');
            $table->timestamps();

            $table->unique(['option_id', 'langue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options_traduites');
    }
};
