<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le soutien entre pairs passe par ce binome physique, jamais par un fil de discussion.
        Schema::create('binomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_a_id')->unique()->constrained('parents')->cascadeOnDelete();
            $table->foreignId('parent_b_id')->unique()->constrained('parents')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('binomes');
    }
};
