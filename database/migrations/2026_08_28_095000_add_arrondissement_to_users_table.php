<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perimetre de lecture du superviseur.
     *
     *   arrondissement renseigne -> delegation d'arrondissement : elle ne voit
     *                               que ses propres facilitateurs, ses cohortes
     *                               et ses ecarts ;
     *   arrondissement a null    -> delegation departementale : elle voit les
     *                               huit arrondissements de la Mvila.
     *
     * Une delegation d'arrondissement n'a pas a lire les ecarts d'une autre :
     * l'ecart se lit AVEC le facilitateur concerne, et son superieur direct est
     * le seul a en avoir l'usage.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('arrondissement')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('arrondissement');
        });
    }
};
