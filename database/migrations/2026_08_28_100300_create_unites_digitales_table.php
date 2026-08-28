<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unites_digitales', function (Blueprint $table) {
            $table->id();
            // NON NULLABLE des deux cotes : une unite sans rattachement au curriculum
            // ne peut pas etre citee avec sa reference, donc l'assistant ne peut pas la servir.
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            // Champ compare au texte du parent par l'assistant a corpus ferme.
            $table->text('message_cle');
            $table->timestamps();

            $table->fullText('message_cle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unites_digitales');
    }
};
