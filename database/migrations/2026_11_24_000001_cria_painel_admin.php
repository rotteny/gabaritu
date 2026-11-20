<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Painel admin (docs/admin.md): conta própria com 2FA, auditoria e métricas agregadas.
 * Nenhuma tabela guarda IP, user-agent ou id de aluno nas métricas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->text('dois_fatores_segredo')->nullable();
            $table->text('codigos_recuperacao')->nullable();
            $table->unsignedBigInteger('ultimo_passo_totp')->nullable();
            $table->timestamp('confirmado_em')->nullable();
            $table->timestamp('ultimo_login_em')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('admin_email');
            $table->string('acao', 40);
            $table->string('alvo_tipo', 20)->nullable();
            $table->unsignedBigInteger('alvo_id')->nullable();
            $table->string('motivo', 200)->nullable();
            $table->jsonb('detalhes')->nullable();
            $table->timestamp('criado_em')->useCurrent();
            $table->index('criado_em');
            $table->index(['alvo_tipo', 'alvo_id']);
        });

        Schema::create('metricas_diarias', function (Blueprint $table) {
            $table->date('dia')->primary();
            $table->unsignedInteger('alunos_ativos')->default(0);
            $table->unsignedInteger('logins')->default(0);
            $table->unsignedInteger('respostas')->default(0);
            $table->unsignedInteger('visitas_landing')->default(0);
        });

        Schema::table('respostas', function (Blueprint $table) {
            $table->index('respondida_em');
            $table->index(['questao_id', 'alternativa_id']);
        });

        $this->preencherRespostasDoHistorico();
    }

    public function down(): void
    {
        Schema::table('respostas', function (Blueprint $table) {
            $table->dropIndex(['respondida_em']);
            $table->dropIndex(['questao_id', 'alternativa_id']);
        });
        Schema::dropIfExists('metricas_diarias');
        Schema::dropIfExists('admin_auditoria');
        Schema::dropIfExists('admins');
    }

    /** As respostas já existem, então a série diária começa completa; as demais métricas só valem daqui para a frente. */
    private function preencherRespostasDoHistorico(): void
    {
        DB::insert(
            'insert into metricas_diarias (dia, respostas) '
            .'select (respondida_em at time zone ? at time zone ?)::date as dia, count(*) from respostas group by dia',
            [config('app.timezone'), config('gabaritu.fuso')],
        );
    }
};
