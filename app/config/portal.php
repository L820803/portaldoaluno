<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identidade
    |--------------------------------------------------------------------------
    |
    | O nome da prefeitura e o logo vêm do e-cidade (DadosPrefeitura); estes
    | valores são usados quando o e-cidade não responde.
    |
    */

    'prefeitura' => env('PORTAL_PREFEITURA', 'Prefeitura do Município'),

    // Equivalente ao Configure "Prefeitura.orgao" do legado
    'orgao' => env('PORTAL_ORGAO', 'Secretaria de Educação'),

    /*
    |--------------------------------------------------------------------------
    | Usuários
    |--------------------------------------------------------------------------
    |
    | O portal não usa banco de dados: os usuários ficam num arquivo JSON,
    | criado na primeira leitura a partir da semente (senhas em texto puro).
    | Arquivo vazio = só em memória (testes).
    |
    */

    'usuarios' => [
        'arquivo' => env('PORTAL_USUARIOS_ARQUIVO', storage_path('app/private/usuarios.json')),
        'semente' => database_path('fixtures/usuarios.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Primeiro acesso
    |--------------------------------------------------------------------------
    |
    | Como no legado, a senha inicial do aluno é o próprio código. Ao criar a
    | senha definitiva, o aluno confirma a data de nascimento: sem isso, quem
    | adivinhasse um código assumiria a conta de quem ainda não acessou.
    |
    */

    'confirmar_nascimento' => (bool) env('PORTAL_CONFIRMAR_NASCIMENTO', true),

    // Validade do link de confirmação de e-mail
    'email_link_horas' => (int) env('PORTAL_EMAIL_LINK_HORAS', 24),

];
