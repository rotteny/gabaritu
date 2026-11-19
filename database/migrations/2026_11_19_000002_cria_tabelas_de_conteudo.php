<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nome');
            $table->string('area');
            $table->unsignedSmallInteger('ordem')->default(0);
        });

        Schema::create('assuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->string('slug', 80);
            $table->string('nome');
            $table->unsignedSmallInteger('ordem');
            $table->text('por_que')->nullable();
            $table->unique(['materia_id', 'slug']);
        });

        Schema::create('etiquetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->string('codigo', 80)->unique();
            $table->string('nome');
            $table->text('descricao')->nullable();
        });

        Schema::create('questionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $table->string('titulo');
            $table->unsignedSmallInteger('ordem');
            $table->string('tipo', 20)->default('assunto');
            $table->unique(['assunto_id', 'ordem']);
        });

        Schema::create('questoes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $table->foreignId('questionario_id')->nullable()->constrained('questionarios')->nullOnDelete();
            $table->smallInteger('dificuldade');
            $table->string('contexto')->nullable();
            $table->text('enunciado');
            $table->text('explicacao');
            $table->string('fonte', 60)->nullable();
            $table->string('licenca', 40)->nullable();
            $table->unsignedInteger('chute_limite_ms');
            $table->string('status', 20)->default('ativa');
            $table->string('anulada_motivo')->nullable();
            $table->timestamp('anulada_em')->nullable();
        });

        Schema::create('alternativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $table->char('letra', 1);
            $table->text('texto');
            $table->boolean('correta');
            $table->foreignId('etiqueta_id')->nullable()->constrained('etiquetas')->nullOnDelete();
            $table->unique(['questao_id', 'letra']);
        });
    }

    public function down(): void
    {
        foreach (['alternativas', 'questoes', 'questionarios', 'etiquetas', 'assuntos', 'materias'] as $tabela) {
            Schema::dropIfExists($tabela);
        }
    }
};
