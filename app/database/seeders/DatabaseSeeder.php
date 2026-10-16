<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Aluno das fixtures do e-cidade, ainda no primeiro acesso: entra com 123456 / 123456
        // e cria a senha (data de nascimento 21/03/2013) e o e-mail (chega no Mailpit).
        // Só com ECIDADE_DRIVER=fake: no e-cidade real o código 123456 é de um aluno de verdade.
        if (config('ecidade.driver') !== 'fake') {
            $this->command?->warn('ECIDADE_DRIVER não é "fake": aluno de teste não criado (use um código real no primeiro acesso).');

            return;
        }

        User::factory()->primeiroAcesso()->create([
            'name' => 'MARIA EDUARDA DA SILVA SOUZA',
            'login' => '123456',
        ]);
    }
}
