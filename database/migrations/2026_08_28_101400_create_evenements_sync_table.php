<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal brut de la remontee. Le client envoie des evenements horodates,
        // jamais des etats. On les conserve tels quels et pour toujours :
        // les tables seances / presences / sequences_ouvertes / fiches_fidelite
        // n'en sont que la projection courante. Rien n'est jamais ecrase.
        Schema::create('evenements_sync', function (Blueprint $table) {
            $table->id();
            // Genere par le client. Un renvoi du meme evenement est ignore en silence.
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->uuid('seance_uuid')->nullable();
            $table->json('charge');
            $table->dateTime('emis_a');
            $table->dateTime('recu_a');

            $table->index('seance_uuid');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_sync');
    }
};
