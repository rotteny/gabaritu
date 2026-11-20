<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questoes', function (Blueprint $table) {
            $table->foreignId('assunto_id')->nullable()->change();
            $table->text('explicacao')->nullable()->change();
            $this->colunasDeImagem($table);
            $table->string('origem', 20)->default('autoral');
            $table->jsonb('origem_ref')->nullable();
            $table->string('revisao_juridica', 20)->nullable();
            $table->boolean('alt_pendente')->default(false);
            $table->boolean('anulada')->default(false);
            $table->string('area')->nullable();
            $table->string('lingua', 20)->nullable();
            $table->index('origem');
        });

        Schema::table('alternativas', function (Blueprint $table) {
            $this->colunasDeImagem($table);
        });
    }

    public function down(): void
    {
        Schema::table('alternativas', function (Blueprint $table) {
            $table->dropColumn(['imagem_url', 'imagem_alt', 'imagem_largura', 'imagem_altura']);
        });

        Schema::table('questoes', function (Blueprint $table) {
            $table->dropIndex(['origem']);
            $table->dropColumn([
                'imagem_url', 'imagem_alt', 'imagem_largura', 'imagem_altura',
                'origem', 'origem_ref', 'revisao_juridica', 'alt_pendente', 'anulada', 'area', 'lingua',
            ]);
        });
    }

    private function colunasDeImagem(Blueprint $table): void
    {
        $table->string('imagem_url')->nullable();
        $table->text('imagem_alt')->nullable();
        $table->unsignedSmallInteger('imagem_largura')->nullable();
        $table->unsignedSmallInteger('imagem_altura')->nullable();
    }
};
