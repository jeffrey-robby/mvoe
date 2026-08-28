<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SEULES personnes nominatives du systeme. Ce sont des agents publics :
        // leur nom et leur telephone sont l'annuaire que le parent consulte.
        Schema::create('facilitateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone');
            $table->string('arrondissement');
            $table->date('date_formation');
            // Nullable : un facilitateur forme mais jamais actif est precisement
            // ce que le registre doit rendre visible.
            $table->date('derniere_activite')->nullable();
            $table->timestamps();

            $table->index('arrondissement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilitateurs');
    }
};
