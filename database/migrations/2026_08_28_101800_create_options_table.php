<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->string('pictogramme');
            // Usage strictement analytique. N'est JAMAIS renvoye a l'espace parent
            // ni rendu a l'ecran : le parent ne voit ni bonne ni mauvaise reponse.
            $table->boolean('est_attendue')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
