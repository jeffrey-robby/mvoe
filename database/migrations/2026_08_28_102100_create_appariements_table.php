<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal de l'assistant, SANS parent_id et sans aucun identifiant de session.
        // Sert a reperer les questions que le corpus ne couvre pas encore.
        // Ne sert jamais a profiler qui que ce soit.
        Schema::create('appariements', function (Blueprint $table) {
            $table->id();
            $table->text('texte_question');
            // Null = refus assume : aucune unite n'a depasse le seuil.
            $table->foreignId('unite_id')->nullable()->constrained('unites_digitales')->nullOnDelete();
            $table->decimal('score', 6, 3);
            $table->dateTime('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appariements');
    }
};
