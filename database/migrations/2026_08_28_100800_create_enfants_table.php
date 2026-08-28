<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AUCUNE date de naissance : une tranche d'age suffit au programme
        // et ne permet pas de reidentifier un enfant.
        Schema::create('enfants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->enum('tranche_age', ['0_2', '3_5', '6_11', '12_17']);
            $table->enum('sexe', ['f', 'm']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enfants');
    }
};
