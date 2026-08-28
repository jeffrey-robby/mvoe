<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // LE DECLARE. Saisi par le facilitateur APRES la seance, jamais pendant.
        Schema::create('fiches_fidelite', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seance_id')->constrained()->cascadeOnDelete();
            // Nullable : la ligne sans sequence porte le champ libre de fin de seance
            // (« qu'est-ce qui a le moins bien marche ? »), qui vaut pour toute la seance.
            $table->foreignId('sequence_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('realisee_bool')->nullable();
            $table->unsignedTinyInteger('note_qualite')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->unique(['seance_id', 'sequence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiches_fidelite');
    }
};
