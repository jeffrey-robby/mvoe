<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Un binome nait DANS un groupe de soutien.
        |
        | Jusqu'ici les binomes flottaient : deux parents lies, sans savoir par
        | qui ni dans quel cadre. Le guide officiel les constitue au sein d'un
        | groupe, et c'est ce groupe qui les fait vivre. Nullable, parce que les
        | binomes de la premiere cohorte de demonstration ont ete formes avant
        | que le groupe n'existe : le dire vaut mieux que d'inventer un
        | rattachement.
        */
        Schema::table('binomes', function (Blueprint $table) {
            $table->foreignId('gsp_id')->nullable()->after('id')
                ->constrained('groupes_soutien')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('binomes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gsp_id');
        });
    }
};
