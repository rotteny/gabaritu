<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** O texto-base do ENEM passa de 2.000 caracteres: `string(255)` não comporta. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questoes', function (Blueprint $table): void {
            $table->text('contexto')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('questoes', function (Blueprint $table): void {
            $table->string('contexto')->nullable()->change();
        });
    }
};
