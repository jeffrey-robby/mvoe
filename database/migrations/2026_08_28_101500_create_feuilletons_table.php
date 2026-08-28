<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feuilletons', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->enum('langue', ['fr', 'en', 'bulu']);
            $table->text('resume');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feuilletons');
    }
};
