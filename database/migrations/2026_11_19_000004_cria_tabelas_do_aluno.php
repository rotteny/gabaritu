<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tentativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('questionario_id')->nullable()->constrained('questionarios')->nullOnDelete();
            $table->foreignId('assunto_id')->nullable()->constrained('assuntos')->nullOnDelete();
            $table->string('tipo', 20);
            $table->unsignedInteger('seed');
            $table->timestamp('iniciada_em');
            $table->timestamp('finalizada_em')->nullable();
            $table->unsignedSmallInteger('acertos')->nullable();
            $table->index(['user_id', 'questionario_id']);
        });

        Schema::create('tentativa_questoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentativa_id')->constrained('tentativas')->cascadeOnDelete();
            $table->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $table->unsignedSmallInteger('posicao');
            $table->timestamp('servida_em', 3)->nullable();
            $table->unique(['tentativa_id', 'questao_id']);
        });

        Schema::create('respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tentativa_id')->constrained('tentativas')->cascadeOnDelete();
            $table->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $table->foreignId('alternativa_id')->constrained('alternativas')->cascadeOnDelete();
            $table->foreignId('variacao_fala_id')->nullable()->constrained('variacoes_fala')->nullOnDelete();
            $table->string('contexto', 20);
            $table->boolean('correta');
            $table->boolean('chute');
            $table->boolean('repetida');
            $table->timestamp('servida_em', 3);
            $table->timestamp('respondida_em', 3);
            $table->unsignedInteger('tempo_ms');
            $table->unique(['tentativa_id', 'questao_id']);
            $table->index(['user_id', 'questao_id']);
            $table->index(['user_id', 'respondida_em']);
        });

        Schema::create('desempenhos_assunto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $table->double('theta')->default(0);
            $table->unsignedInteger('n_primeiras')->default(0);
            $table->unsignedSmallInteger('nota')->nullable();
            $table->timestamp('calculado_em')->nullable();
            $table->unique(['user_id', 'assunto_id']);
        });

        Schema::create('desempenho_assunto_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $table->foreignId('resposta_id')->constrained('respostas')->cascadeOnDelete();
            $table->double('theta');
            $table->unsignedSmallInteger('nota');
            $table->unsignedInteger('n_primeiras');
            $table->timestamp('calculado_em');
            $table->index(['user_id', 'assunto_id', 'calculado_em']);
        });

        Schema::create('revisoes_espacadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $table->unsignedSmallInteger('caixa');
            $table->unsignedSmallInteger('acertos_seguidos')->default(0);
            $table->date('proxima_em');
            $table->unique(['user_id', 'questao_id']);
            $table->index(['user_id', 'proxima_em']);
        });

        Schema::create('feedbacks_fala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('variacao_fala_id')->constrained('variacoes_fala')->cascadeOnDelete();
            $table->boolean('legal');
            $table->timestamp('criado_em')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['feedbacks_fala', 'revisoes_espacadas', 'desempenho_assunto_historico', 'desempenhos_assunto', 'respostas', 'tentativa_questoes', 'tentativas'] as $tabela) {
            Schema::dropIfExists($tabela);
        }
    }
};
