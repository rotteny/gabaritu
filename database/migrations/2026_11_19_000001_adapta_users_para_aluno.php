<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'apelido');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('ano_nascimento')->nullable();
            $table->string('serie', 20)->nullable();
            $table->string('zoeira', 4)->default('fofo');
            $table->boolean('som')->default(true);
            $table->boolean('reduzir_movimento')->default(false);
            $table->string('ultima_materia', 60)->nullable();
            $table->timestamp('ultimo_acesso_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ano_nascimento', 'serie', 'zoeira', 'som', 'reduzir_movimento', 'ultima_materia', 'ultimo_acesso_em']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('apelido', 'name');
        });
    }
};
