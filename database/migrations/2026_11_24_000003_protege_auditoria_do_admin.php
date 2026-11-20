<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A auditoria é só de inclusão também no banco: update e delete levantam exceção, mesmo por SQL direto.
 * Única exceção: o `on delete set null` do admin_id quando a conta do admin é apagada (nada mais pode mudar na linha).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function admin_auditoria_somente_inclusao() returns trigger language plpgsql as $$
            begin
                if tg_op = 'UPDATE'
                    and old.admin_id is not null and new.admin_id is null
                    and new.id = old.id
                    and new.admin_email = old.admin_email
                    and new.acao = old.acao
                    and new.alvo_tipo is not distinct from old.alvo_tipo
                    and new.alvo_id is not distinct from old.alvo_id
                    and new.motivo is not distinct from old.motivo
                    and new.detalhes is not distinct from old.detalhes
                    and new.criado_em = old.criado_em then
                    return new;
                end if;
                raise exception 'admin_auditoria é somente de inclusão';
            end;
            $$
        SQL);
        DB::unprepared('create trigger admin_auditoria_imutavel before update or delete on admin_auditoria for each row execute function admin_auditoria_somente_inclusao()');
    }

    public function down(): void
    {
        DB::unprepared('drop trigger if exists admin_auditoria_imutavel on admin_auditoria');
        DB::unprepared('drop function if exists admin_auditoria_somente_inclusao()');
    }
};
