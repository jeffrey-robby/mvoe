<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'arrondissement est la maille de base du systeme.
     *
     * Facilitateurs, cohortes, activites, foyers et signalements y sont tous
     * rattaches. Une portee, quel que soit le niveau, se resout donc toujours
     * en une LISTE D'ARRONDISSEMENTS : le national les a tous, une delegation
     * regionale a ceux de sa region, un superviseur en a exactement un.
     *
     * `region_id` est denormalise volontairement : il evite une jointure sur
     * toutes les requetes de portee regionale, et un arrondissement ne change
     * pas de region.
     */
    public function up(): void
    {
        Schema::create('arrondissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->timestamps();

            $table->unique(['departement_id', 'libelle']);
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrondissements');
    }
};
