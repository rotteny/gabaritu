<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacotes_fala', function (Blueprint $table) {
            $table->id();
            $table->string('identificador')->unique();
            $table->string('origem', 20)->default('manual');
            $table->string('revisor')->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });

        Schema::create('chaves_fala', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 120)->unique();
            $table->string('descricao')->nullable();
            $table->boolean('critica')->default(false);
        });

        Schema::create('variacoes_fala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chave_fala_id')->constrained('chaves_fala')->cascadeOnDelete();
            $table->foreignId('pacote_fala_id')->nullable()->constrained('pacotes_fala')->nullOnDelete();
            $table->string('hash', 40)->unique();
            $table->text('texto');
            $table->string('nivel', 4);
            $table->unsignedSmallInteger('idade_minima')->default(13);
            $table->string('tema')->nullable();
            $table->date('inicio_em')->nullable();
            $table->date('validade_em')->nullable();
            $table->boolean('reserva')->default(false);
            $table->string('status_revisao', 20)->default('aprovada');
            $table->string('revisado_por')->nullable();
            $table->index(['chave_fala_id', 'nivel']);
        });
    }

    public function down(): void
    {
        foreach (['variacoes_fala', 'chaves_fala', 'pacotes_fala'] as $tabela) {
            Schema::dropIfExists($tabela);
        }
    }
};
