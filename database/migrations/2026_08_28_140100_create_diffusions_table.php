<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Les diffusions par canal : SMS, USSD, vocal, radio.
        |
        | Les pilotes sont FACTICES dans ce prototype. Le passage a une
        | infrastructure nationale ne change qu'un pilote — c'est tout
        | l'argument de replicabilite, et il doit se voir dans le code.
        |
        | POUR LA RADIO, AUCUNE AUDIENCE N'EST FABRIQUEE. On enregistre les
        | diffusions attestees, et on mesure le surcroit d'appels vocaux et de
        | sessions USSD dans les 48 heures qui suivent. C'est la seule mesure
        | d'effet radio qui soit honnete : une station qui annonce « deux
        | millions d'auditeurs » n'a compte personne.
        */
        Schema::create('diffusions', function (Blueprint $table) {
            $table->id();
            $table->string('canal');
            $table->foreignId('unite_id')->nullable()->constrained('unites_digitales')->nullOnDelete();
            $table->foreignId('langue_id')->constrained('langues')->cascadeOnDelete();
            $table->foreignId('campagne_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('arrondissement_id')->nullable()->constrained()->nullOnDelete();

            // Ce que le canal a vise et ce qu'il a fait. Le sens de `volume`
            // depend du canal : envois pour le SMS, sessions pour l'USSD,
            // appels pour le vocal, une seule diffusion pour la radio.
            $table->string('cible');
            $table->dateTime('date');
            $table->unsignedInteger('volume');
            $table->unsignedInteger('aboutis')->default(0);
            $table->string('statut')->default('planifiee');

            // Radio seulement : qui atteste que la diffusion a bien eu lieu.
            // Sans attestation, une diffusion declaree n'est qu'une intention.
            $table->string('atteste_par')->nullable();
            $table->timestamps();

            $table->index(['canal', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diffusions');
    }
};
