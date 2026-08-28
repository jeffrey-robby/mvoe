<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feuilleton_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->string('titre');
            $table->string('fichier_audio')->nullable();
            $table->unsignedSmallInteger('duree');
            // Rattachement optionnel a une unite du curriculum : le feuilleton
            // illustre le module 8 sans etre du curriculum lui-meme.
            $table->foreignId('unite_id')->nullable()->constrained('unites_digitales')->nullOnDelete();
            $table->timestamps();

            $table->unique(['feuilleton_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
