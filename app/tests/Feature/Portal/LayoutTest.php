<?php

use App\Models\User;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\Gateway;
use Inertia\Testing\AssertableInertia as Assert;

// PNG 1x1
const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

beforeEach(function () {
    $this->gateway = app(Gateway::class);
    $this->aluno = User::factory()->create(['login' => '123456', 'name' => 'NOME LOCAL']);
});

function alunoDadosCom(array $extra): array
{
    $dados = json_decode(file_get_contents(database_path('fixtures/ecidade/AlunoDados.json')), true);

    return array_replace($dados, $extra);
}

test('compartilha o resumo do aluno e a identidade do portal', function () {
    $this->actingAs($this->aluno)
        ->get('/inicio')
        ->assertInertia(fn (Assert $page) => $page
            ->where('portal.prefeitura', 'Prefeitura Municipal')
            ->where('portal.orgao', config('portal.orgao'))
            ->where('portal.logo', null)
            ->where('aluno.codigo', '123456')
            ->where('aluno.nome', 'MARIA EDUARDA DA SILVA SOUZA')
            // As fixtures do e-cidade trazem uma foto
            ->where('aluno.foto', fn (string $url) => str_starts_with($url, route('aluno.foto').'?v='))
            ->where('aluno.indisponivel', false)
            ->where('aluno.matriculas', [
                ['codigo' => 87654, 'ano' => 2025, 'etapa' => '7º ANO'],
                ['codigo' => 98765, 'ano' => 2026, 'etapa' => '8º ANO'],
            ])
            // Como no legado: a matrícula mais recente vem selecionada
            ->where('aluno.matriculaAtiva', 98765)
            ->missing('auth.user.password'));
});

test('usuário que não é aluno (login não numérico) não consulta o e-cidade', function () {
    $this->actingAs(User::factory()->create(['login' => 'admin']))
        ->get('/inicio')
        ->assertInertia(fn (Assert $page) => $page->where('aluno', null));

    $this->gateway->assertNotCalled('AlunoDados');
});

test('o aluno pode trocar a matrícula consultada', function () {
    $this->actingAs($this->aluno)
        ->from('/boletim')
        ->put('/matricula-ativa', ['matricula' => 87654])
        ->assertRedirect('/boletim');

    $this->get('/inicio')->assertInertia(fn (Assert $page) => $page->where('aluno.matriculaAtiva', 87654));
});

test('não é possível selecionar matrícula de outro aluno', function () {
    $this->actingAs($this->aluno)
        ->put('/matricula-ativa', ['matricula' => 11111])
        ->assertForbidden();

    $this->get('/inicio')->assertInertia(fn (Assert $page) => $page->where('aluno.matriculaAtiva', 98765));
});

test('matrícula inválida é rejeitada na validação', function () {
    $this->actingAs($this->aluno)
        ->put('/matricula-ativa', ['matricula' => 'abc'])
        ->assertSessionHasErrors('matricula');
});

test('com o e-cidade fora do ar o portal abre e avisa o aluno', function () {
    $this->gateway->respondWith('AlunoDados', new ECidadeUnavailableException('timeout'));
    $this->gateway->respondWith('DadosPrefeitura', new ECidadeUnavailableException('timeout'));

    $this->actingAs($this->aluno)
        ->get('/inicio')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('aluno.indisponivel', true)
            ->where('aluno.nome', 'NOME LOCAL')
            ->where('aluno.matriculas', [])
            ->where('portal.prefeitura', config('portal.prefeitura')));
});

test('foto do aluno é servida como imagem', function () {
    $this->gateway->respondWith('AlunoDados', alunoDadosCom(['foto_aluno' => PNG_BASE64]));

    $this->actingAs($this->aluno)
        ->get('/inicio')
        ->assertInertia(fn (Assert $page) => $page->where('aluno.foto', route('aluno.foto', ['v' => substr(md5(base64_decode(PNG_BASE64)), 0, 8)])));

    $this->get('/aluno/foto')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('sem foto no e-cidade a rota responde 404', function () {
    $this->gateway->respondWith('AlunoDados', alunoDadosCom(['foto_aluno' => '']));

    $this->actingAs($this->aluno)->get('/aluno/foto')->assertNotFound();
});

test('conteúdo que não é imagem não é servido como foto', function () {
    $this->gateway->respondWith('AlunoDados', alunoDadosCom(['foto_aluno' => base64_encode('<script>alert(1)</script>')]));

    $this->actingAs($this->aluno)->get('/aluno/foto')->assertNotFound();
});

test('a foto exige login', function () {
    $this->get('/aluno/foto')->assertRedirect('/login');
});

test('logo da prefeitura é público', function () {
    $this->gateway->respondWith('DadosPrefeitura', ['sDescricao' => 'Prefeitura X', 'sLogoPrefeituraBaseEncode' => PNG_BASE64]);

    $this->get('/prefeitura/logo')->assertOk()->assertHeader('Content-Type', 'image/png');

    $this->get('/login')->assertInertia(fn (Assert $page) => $page
        ->where('portal.logo', route('prefeitura.logo'))
        ->where('portal.prefeitura', 'Prefeitura X')
        ->where('aluno', null));
});

test('mensagens de sessão são compartilhadas', function () {
    $this->actingAs($this->aluno)
        ->withSession(['success' => 'Tudo certo!'])
        ->get('/inicio')
        ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Tudo certo!')->where('flash.error', null));
});
