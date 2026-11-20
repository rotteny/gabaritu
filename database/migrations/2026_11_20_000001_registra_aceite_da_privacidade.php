<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Prova do aceite (LGPD art. 8º, § 2º: o ônus da prova é do controlador): quando e qual versão da política. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacidade_aceita_em')->nullable();
            $table->string('privacidade_versao', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['privacidade_aceita_em', 'privacidade_versao']);
        });
    }
};
