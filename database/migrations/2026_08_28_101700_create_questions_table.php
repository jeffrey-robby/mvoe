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
            $table->text('enonce');
            $table->string('enonce_audio')->nullable();
            // Ce que propose le programme, et pourquoi. Porte par la QUESTION et
            // non par l'option : le texte lu est le meme quelle que soit la
            // reponse du parent. C'est la traduction en base de « jamais de
            // verdict, jamais de score ».
            $table->text('explication');
            $table->unsignedTinyInteger('ordre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
