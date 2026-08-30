<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Les groupes de soutien parental.
        |
        | Le guide officiel cree ces groupes et leur assigne une mission qui
        | survit au programme, mais rien ne les suit aujourd'hui. `derniere_reunion`
        | est l'indicateur de continuite du dossier : un groupe sans reunion
        | depuis huit mois n'est pas un groupe, c'est une ligne dans un rapport.
        */
        Schema::create('groupes_soutien', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('libelle');
            $table->foreignId('cohorte_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('arrondissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facilitateur_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_creation');
            // Recalculee a chaque reunion remontee. Nullable : un groupe qui
            // vient d'etre cree ne s'est pas encore reuni, et le dire vaut
            // mieux que d'inventer une date.
            $table->date('derniere_reunion')->nullable();
            $table->timestamps();

            $table->index('arrondissement_id');
        });

        Schema::create('membres_gsp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gsp_id')->constrained('groupes_soutien')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['gsp_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres_gsp');
        Schema::dropIfExists('groupes_soutien');
    }
};
