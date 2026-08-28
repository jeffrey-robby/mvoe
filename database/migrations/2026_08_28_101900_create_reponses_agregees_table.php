<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AGREGE. On incremente un compteur, sans aucun parent_id :
        // on sait combien de parents ont choisi une option, jamais lesquels.
        Schema::create('reponses_agregees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('compteur')->default(0);
            $table->timestamps();

            $table->unique(['question_id', 'option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_agregees');
    }
};
