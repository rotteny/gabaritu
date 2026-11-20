<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Responsável legal (docs/responsaveis.md). Dados mínimos: nome (em apelido), e-mail, ano de
 * nascimento, parentesco e o registro do aceite. Nada de CPF, telefone, endereço ou documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipo', 12)->default('aluno');
            $table->string('status', 24)->default('ativo');
            $table->string('zoeira_responsavel', 4)->nullable();
            // Avatar de uma lista fixa de bichos marinhos (config gabaritu.avatares). Sem upload de foto, de propósito.
            $table->string('avatar', 30)->nullable();
            $table->string('email_responsavel')->nullable();
            $table->string('parentesco_responsavel', 10)->nullable();
            $table->string('parentesco_responsavel_outro', 40)->nullable();
            $table->timestamp('pendente_desde')->nullable();
            $table->timestamp('aviso_remocao_em')->nullable();
            $table->timestamp('sem_aluno_desde')->nullable();
            $table->index(['tipo', 'status']);
        });

        Schema::create('vinculos_responsavel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('responsavel_id')->constrained('users')->cascadeOnDelete();
            $table->string('parentesco', 10);
            $table->string('parentesco_outro', 40)->nullable();
            $table->timestamp('aceito_em');
            $table->string('termos_versao', 20);
            $table->string('privacidade_versao', 20);
            $table->string('aceite_ip', 45)->nullable();
            $table->boolean('declara_responsavel');
            $table->boolean('aceite_termos');
            $table->string('status', 10)->default('ativo');
            $table->timestamp('suspenso_em')->nullable();
            $table->timestamps();
            $table->unique(['aluno_id', 'responsavel_id']);
        });

        Schema::create('convites_responsavel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('solicitante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('substitui_responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 24);
            $table->string('email');
            $table->string('parentesco', 10);
            $table->string('parentesco_outro', 40)->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expira_em');
            $table->timestamp('usado_em')->nullable();
            $table->timestamp('recusado_em')->nullable();
            $table->timestamp('invalidado_em')->nullable();
            // Pedido iniciado pelo responsável: o aceite dele fica registrado no pedido; o do aluno vem depois.
            $table->string('aceite_ip', 45)->nullable();
            $table->timestamp('aceite_em')->nullable();
            $table->string('termos_versao', 20)->nullable();
            $table->boolean('exige_aprovacao')->default(false);
            $table->foreignId('aprovado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprovado_em')->nullable();
            $table->timestamp('aluno_aceitou_em')->nullable();
            $table->timestamps();
            $table->index(['aluno_id', 'tipo', 'created_at']);
            $table->index('expira_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convites_responsavel');
        Schema::dropIfExists('vinculos_responsavel');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'status']);
            $table->dropColumn(['tipo', 'status', 'zoeira_responsavel', 'avatar', 'email_responsavel', 'parentesco_responsavel', 'parentesco_responsavel_outro', 'pendente_desde', 'aviso_remocao_em', 'sem_aluno_desde']);
        });
    }
};
