<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Prévia local: um responsável com 2 alunos. Login: responsavel@gabaritu.test / senha-previa-123 */
class ResponsavelDePreviaSeeder extends Seeder
{
    public function run(): void
    {
        $responsavel = User::factory()->responsavel()->create([
            'apelido' => 'Dona Marta',
            'email' => 'responsavel@gabaritu.test',
            'password' => 'senha-previa-123',
        ]);

        User::factory()->comIdade(15)->comResponsavel($responsavel)->create(['apelido' => 'Joaozinho', 'email' => 'joao@gabaritu.test', 'serie' => '1em', 'password' => 'senha-previa-123']);
        User::factory()->comIdade(14)->comResponsavel($responsavel)->create(['apelido' => 'Aninha', 'email' => 'ana@gabaritu.test', 'serie' => '9ano', 'password' => 'senha-previa-123']);
    }
}
