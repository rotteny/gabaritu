<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prova do aceite e da aprovação de um convite/pedido usado. Fica fora de `convites_responsavel` de
 * propósito: o convite vencido é apagado pela limpeza e não pode levar a prova junto. `convite_id` é só
 * referência (sem chave estrangeira). A prova some junto com a conta do aluno (como o vínculo); o responsável vira nulo se apagar a conta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aceites_responsavel', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('convite_id')->nullable()->index();
            $table->string('tipo', 24);
            $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprovado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aceito_em');
            $table->string('aceite_ip', 45)->nullable();
            $table->string('termos_versao', 20);
            $table->string('privacidade_versao', 20);
            $table->timestamp('aluno_aceitou_em')->nullable();
            $table->timestamp('aprovado_em')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aceites_responsavel');
    }
};
