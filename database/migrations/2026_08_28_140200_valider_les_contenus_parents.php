<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Un contenu non valide ne peut pas etre diffuse.
        |
        | La regle valait deja pour les modules de formation ; elle vaut
        | desormais pour ce qu'un PARENT recoit. C'est la ou elle compte le
        | plus : une realisation mal relue qui part en audio dans une langue
        | qu'on ne relit pas se rattrape mal.
        |
        | Par defaut `valide` : les realisations deja chargees ont ete relues
        | avant d'entrer en base. Une nouvelle naitra en brouillon.
        */
        Schema::table('realisations', function (Blueprint $table) {
            $table->string('statut_validation')->default('valide')->after('modalite');
            $table->index('statut_validation');
        });
    }

    public function down(): void
    {
        Schema::table('realisations', function (Blueprint $table) {
            $table->dropIndex(['statut_validation']);
            $table->dropColumn('statut_validation');
        });
    }
};
