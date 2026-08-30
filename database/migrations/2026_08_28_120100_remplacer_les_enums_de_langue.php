<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Les quatre endroits ou une langue etait figee dans le schema. */
    private const TABLES = [
        'parents' => 'langue_pref',
        'realisations' => 'langue',
        'feuilletons' => 'langue',
        'situations_frequentes' => 'langue',
    ];

    public function up(): void
    {
        /*
        | On remplace quatre colonnes `enum('fr','en','bulu')` par une cle
        | etrangere vers `langues`.
        |
        | Les tables sont vides a ce stade (les seeders passent apres les
        | migrations) : on peut donc supprimer et recreer sans conversion de
        | donnees. Sur une base en production, cette migration devrait d'abord
        | remplir `langue_id` depuis l'ancienne colonne.
        */
        /*
        | L'index unique porte sur l'ancienne colonne : il doit tomber avant
        | elle. Mais MySQL s'en sert aussi pour soutenir la cle etrangere
        | `unite_id`, dont il est le prefixe. On pose donc d'abord un index
        | ordinaire sur cette colonne, sinon MySQL refuse de laisser la
        | contrainte sans support.
        */
        Schema::table('realisations', function (Blueprint $t) {
            $t->index('unite_id', 'realisations_unite_id_index');
        });

        Schema::table('realisations', function (Blueprint $t) {
            $t->dropUnique(['unite_id', 'langue', 'modalite']);
        });

        foreach (self::TABLES as $table => $colonne) {
            Schema::table($table, function (Blueprint $t) use ($colonne) {
                $t->dropColumn($colonne);
            });

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('langue_id')->constrained('langues')->cascadeOnDelete();
            });
        }

        // L'unicite portait sur l'ancienne colonne : on la retablit sur la
        // nouvelle. Une unite n'a qu'une realisation par langue et modalite.
        Schema::table('realisations', function (Blueprint $t) {
            $t->unique(['unite_id', 'langue_id', 'modalite']);
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $colonne) {
            Schema::table($table, function (Blueprint $t) use ($colonne) {
                $t->dropConstrainedForeignId('langue_id');
                $t->string($colonne)->default('fr');
            });
        }
    }
};
