<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AUCUN nom, prenom, profession, religion, ethnie, GPS, e-mail, mot de passe.
        // Le facilitateur reconnait ses parents par un libelle local stocke dans
        // IndexedDB sur son seul appareil, jamais synchronise, jamais present ici.
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohorte_id')->constrained()->cascadeOnDelete();
            $table->string('code_parent')->unique();
            // Code a 4 chiffres remis en main propre. Stocke hache : une copie de la base
            // ne doit pas suffire a entrer dans l'espace parent.
            $table->string('code_acces');
            $table->enum('langue_pref', ['fr', 'en', 'bulu']);
            $table->enum('statut_matrimonial', ['union', 'seul', 'non_renseigne']);
            $table->enum('revenu_regularite', ['regulier', 'irregulier', 'aucun', 'non_renseigne']);
            $table->boolean('telephone_partage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
