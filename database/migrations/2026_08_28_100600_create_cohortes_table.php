<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohortes', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('arrondissement');
            // Volontairement SANS valeur par defaut : le 20 ne doit exister
            // nulle part dans le code, seulement dans la donnee de chaque cohorte.
            $table->unsignedSmallInteger('ratio_max');
            $table->foreignId('curriculum_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facilitateur_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_debut');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohortes');
    }
};
