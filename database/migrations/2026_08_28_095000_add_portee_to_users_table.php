<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La PORTÉE d'un compte administratif.
     *
     * Quatre niveaux, un seul mécanisme :
     *
     *   national      → aucun filtre, les 29 arrondissements
     *   region        → region_id renseigné
     *   departement   → departement_id renseigné
     *   arrondissement→ arrondissement_id renseigné  (= le superviseur)
     *
     * Les trois clés sont nullables et mutuellement exclusives : `niveau` dit
     * laquelle fait foi. On aurait pu faire une relation polymorphe ; trois
     * colonnes explicites se lisent mieux et se contraignent en base.
     *
     * Le facilitateur n'est PAS un `user` : il a sa propre table et son propre
     * mode d'authentification. Sa portée, c'est lui-même.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('niveau', ['national', 'region', 'departement', 'arrondissement'])
                ->after('email');

            $table->foreignId('region_id')->nullable()->after('niveau')
                ->constrained()->nullOnDelete();
            $table->foreignId('departement_id')->nullable()->after('region_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('arrondissement_id')->nullable()->after('departement_id')
                ->constrained()->nullOnDelete();

            // Qui a créé ce compte. Personne ne s'auto-inscrit : le MINPROFF
            // crée les régionales, qui créent les départementales, qui créent
            // les superviseurs. Cette colonne rend la chaîne vérifiable.
            $table->foreignId('cree_par_id')->nullable()->after('arrondissement_id')
                ->constrained('users')->nullOnDelete();

            $table->index('niveau');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('departement_id');
            $table->dropConstrainedForeignId('arrondissement_id');
            $table->dropConstrainedForeignId('cree_par_id');
            $table->dropColumn('niveau');
        });
    }
};
