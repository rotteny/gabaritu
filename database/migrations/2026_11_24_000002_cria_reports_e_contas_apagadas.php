<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O vínculo com o aluno existe para barrar abuso; some junto com a conta (cascade). O painel só mostra o apelido.
        Schema::create('reports_questao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('motivo', 20);
            $table->string('comentario', 300)->nullable();
            $table->string('status', 12)->default('aberto');
            $table->timestamp('resolvido_em')->nullable();
            $table->timestamps();
            $table->unique(['questao_id', 'user_id']);
            $table->index(['status', 'questao_id']);
            $table->index(['user_id', 'created_at']);
        });

        // Só números: conta apagada não deixa dado nenhum (docs/admin.md, pendências legais).
        Schema::create('contas_apagadas_mes', function (Blueprint $table) {
            $table->date('mes')->primary();
            $table->unsignedInteger('alunos')->default(0);
            $table->unsignedInteger('responsaveis')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_apagadas_mes');
        Schema::dropIfExists('reports_questao');
    }
};
