<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le texte d'une question de la semaine, dans une langue.
     *
     * Meme separation que unites_digitales / realisations : la structure d'un
     * cote, le texte de l'autre. Un parent qui choisit le bulu doit lire les
     * questions en bulu -- sinon l'espace parent est bilingue a moitie, ce qui
     * est pire que monolingue.
     *
     * `explication` est ici aussi : c'est ce que le programme propose, et
     * pourquoi. Elle reste portee par la QUESTION et non par l'option, dans
     * toutes les langues. C'est la traduction en base de « jamais de verdict ».
     */
    public function up(): void
    {
        Schema::create('questions_traduites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->enum('langue', ['fr', 'en', 'bulu']);
            $table->text('enonce');
            $table->string('enonce_audio')->nullable();
            $table->text('explication');
            $table->timestamps();

            $table->unique(['question_id', 'langue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_traduites');
    }
};
