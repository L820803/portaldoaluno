<?php

use App\Models\User;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\Gateway;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gateway = app(Gateway::class);
    $this->aluno = User::factory()->create(['login' => '123456']);
});

function alunoDadosAlterado(array $extra): array
{
    $dados = json_decode(file_get_contents(database_path('fixtures/ecidade/AlunoDados.json')), true);

    return array_replace_recursive($dados, $extra);
}

test('as telas cadastrais abrem com os dados do e-cidade', function (string $uri, string $componente) {
    $this->actingAs($this->aluno)
        ->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($componente)->has('dados')->where('erro', false));
})->with([
    ['/meus-dados', 'aluno/meus-dados'],
    ['/documentos', 'aluno/documentos'],
    ['/outras-informacoes', 'aluno/outras-informacoes'],
    ['/minha-escola', 'aluno/minha-escola'],
]);

test('meus dados: identificação, filiação, endereço e contatos', function () {
    $this->actingAs($this->aluno)
        ->get('/meus-dados')
        ->assertInertia(fn (Assert $page) => $page
            ->where('dados.nome', 'MARIA EDUARDA DA SILVA SOUZA')
            ->where('dados.codigo', '123456')
            ->where('dados.nascimento', '2013-03-21')
            ->where('dados.idade', 13)
            ->where('dados.naturalidade', 'PORTO VELHO - RO')
            ->where('dados.filiacao.mae', 'ANA PAULA DA SILVA')
            ->where('dados.endereco.logradouro', 'RUA DAS ACÁCIAS')
            ->where('dados.endereco.zona', 'URBANA')
            ->where('dados.contatos.celular', '(69) 99999-1234')
            ->where('dados.foto', fn (string $url) => str_starts_with($url, route('aluno.foto').'?v=')));
});

test('documentos: grupos vazios viram null', function () {
    $this->actingAs($this->aluno)
        ->get('/documentos')
        ->assertInertia(fn (Assert $page) => $page
            ->where('dados.cpf', '123.456.789-09')
            ->where('dados.passaporte', null)
            ->where('dados.identidade.numero', '1234567')
            ->where('dados.identidade.expedicao', '2020-05-10')
            ->where('dados.certidao.tipo', 'NOVA')
            ->where('dados.certidao.termo', null)
            ->where('dados.cnh', null));
});

test('outras informações: responsável, programas e transporte', function () {
    $this->actingAs($this->aluno)
        ->get('/outras-informacoes')
        ->assertInertia(fn (Assert $page) => $page
            ->where('dados.responsavel.nome', 'ANA PAULA DA SILVA')
            ->where('dados.responsavel.email', 'ana.paula@example.com')
            ->where('dados.programas.bolsaFamilia', 'SIM')
            ->where('dados.transporte.meios', ['ÔNIBUS'])
            ->where('dados.procedencia.data', null));
});

test('minha escola: telefones só com dígitos, diretores e e-mail', function () {
    $this->actingAs($this->aluno)
        ->get('/minha-escola')
        ->assertInertia(fn (Assert $page) => $page
            ->where('dados.nome', 'EMEF PROFESSORA MARIA DE NAZARÉ')
            ->where('dados.telefones', ['6932213344'])
            ->where('dados.email', 'emef.nazare@example.com')
            ->where('dados.site', null)
            ->has('dados.diretores', 2));
});

test('dados malformados do e-cidade são descartados com segurança', function () {
    $this->gateway->respondWith('AlunoDados', alunoDadosAlterado([
        'data_nascimento_aluno' => 'data inválida',
        'outros_dados' => ['email_responsavel_aluno' => 'nao-e-email', 'transportes_utilizados' => ['', 'VAN', null]],
        'oEscola' => ['sUrl' => 'javascript:alert(1)'],
    ]));

    $this->actingAs($this->aluno);

    $this->get('/meus-dados')->assertInertia(fn (Assert $page) => $page->where('dados.nascimento', null));
    $this->get('/outras-informacoes')->assertInertia(fn (Assert $page) => $page
        ->where('dados.responsavel.email', null)
        ->where('dados.transporte.meios', ['VAN']));
    // Link do site sempre http(s)
    $this->get('/minha-escola')->assertInertia(fn (Assert $page) => $page->where('dados.site', 'http://javascript:alert(1)'));
});

test('aluno sem escola informada', function () {
    $this->gateway->respondWith('AlunoDados', alunoDadosAlterado(['oEscola' => ['sNome' => '']]));

    $this->actingAs($this->aluno)
        ->get('/minha-escola')
        ->assertInertia(fn (Assert $page) => $page->where('dados', null)->where('erro', false));
});

test('com o e-cidade fora do ar a tela abre com aviso de erro', function () {
    $this->gateway->respondWith('AlunoDados', new ECidadeUnavailableException('timeout'));

    $this->actingAs($this->aluno)
        ->get('/documentos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('dados', null)->where('erro', true));
});

test('as telas cadastrais exigem cadastro completo', function () {
    $this->actingAs(User::factory()->primeiroAcesso()->create(['login' => '222222']))
        ->get('/meus-dados')
        ->assertRedirect(route('primeiro-acesso'));
});
