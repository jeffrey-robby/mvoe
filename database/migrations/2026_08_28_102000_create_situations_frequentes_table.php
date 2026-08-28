<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entree guidee de l'assistant, pour le parent qui ne sait pas ecrire.
        // Ces libelles ne sont PAS des reponses : ils sont soumis au meme
        // appariement que le texte libre, et certains ne trouvent rien. C'est voulu.
        Schema::create('situations_frequentes', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('pictogramme');
            $table->enum('langue', ['fr', 'en', 'bulu']);
            $table->string('fichier_audio')->nullable();
            $table->unsignedTinyInteger('ordre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('situations_frequentes');
    }
};
