<?php

use App\Models\User;
use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\Gateway;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gateway = app(Gateway::class);
});

test('a tela de login abre', function () {
    $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('aluno entra com código e senha', function () {
    $user = User::factory()->create(['login' => '123456']);

    $this->post('/login', ['login' => ' 123456 ', 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('senha errada não entra e mostra mensagem genérica', function () {
    User::factory()->create(['login' => '123456']);

    $this->post('/login', ['login' => '123456', 'password' => 'errada'])
        ->assertSessionHasErrors(['login' => __('auth.failed')]);

    $this->assertGuest();
});

test('primeiro acesso: cria o usuário a partir do e-cidade quando a senha é o código', function () {
    $this->gateway->respondWith('AlunoLoginDados', ['codigo' => '123456', 'nome' => 'MARIA EDUARDA', 'email' => 'Familia@Example.com']);

    $this->post('/login', ['login' => '123456', 'password' => '123456'])
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::porLogin('123456');
    $this->assertAuthenticatedAs($user);

    expect($user->email)->toBe('familia@example.com')
        ->and($user->validated_email)->toBeFalse()
        ->and(Hash::check('123456', $user->password))->toBeTrue();

    // Cadastro incompleto (senha inicial): vai para o primeiro acesso
    $this->get('/inicio')->assertRedirect(route('primeiro-acesso'));
});

test('primeiro acesso sem e-mail no e-cidade cria o usuário sem e-mail', function () {
    $this->post('/login', ['login' => '123456', 'password' => '123456']);

    expect(User::porLogin('123456')->email)->toBeNull();
});

test('primeiro acesso com senha diferente do código não consulta o e-cidade nem cria usuário', function () {
    $this->post('/login', ['login' => '123456', 'password' => 'outra'])->assertSessionHasErrors('login');

    $this->gateway->assertNotCalled('AlunoLoginDados');
    expect(User::count())->toBe(0);
});

test('código que não é de aluno recebe a mesma mensagem de senha errada', function () {
    $this->gateway->respondWith('AlunoLoginDados', new ECidadeRequestException('Aluno não encontrado'));

    $this->post('/login', ['login' => '999999', 'password' => '999999'])
        ->assertSessionHasErrors(['login' => __('auth.failed')]);

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

test('primeiro acesso com o e-cidade fora do ar explica o problema', function () {
    $this->gateway->respondWith('AlunoLoginDados', new ECidadeUnavailableException('timeout'));

    $this->post('/login', ['login' => '123456', 'password' => '123456'])
        ->assertSessionHasErrors(['login' => (new ECidadeUnavailableException(''))->userMessage()]);

    expect(User::count())->toBe(0);
});

test('aluno que o e-cidade não reconhece mais não entra (como no legado)', function () {
    User::factory()->create(['login' => '123456']);
    $this->gateway->respondWith('AlunoDados', new ECidadeRequestException('Aluno inativo'));

    $this->post('/login', ['login' => '123456', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('com o e-cidade fora do ar o aluno já cadastrado entra mesmo assim', function () {
    User::factory()->create(['login' => '123456']);
    $this->gateway->respondWith('AlunoDados', new ECidadeUnavailableException('timeout'));

    $this->post('/login', ['login' => '123456', 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('ao entrar o nome é atualizado a partir do e-cidade', function () {
    $user = User::factory()->create(['login' => '123456', 'name' => 'NOME ANTIGO']);

    $this->post('/login', ['login' => '123456', 'password' => 'password']);

    expect($user->refresh()->name)->toBe('MARIA EDUARDA DA SILVA SOUZA');
});

test('usuário administrativo (login não numérico) entra sem consultar o e-cidade', function () {
    User::factory()->create(['login' => 'admin']);

    $this->post('/login', ['login' => 'admin', 'password' => 'password'])->assertRedirect();

    $this->assertAuthenticated();
    $this->gateway->assertNotCalled('AlunoDados');
});

test('após 5 senhas erradas o código é bloqueado temporariamente', function () {
    User::factory()->create(['login' => '123456']);

    foreach (range(1, 5) as $_) {
        $this->post('/login', ['login' => '123456', 'password' => 'errada']);
    }

    $this->post('/login', ['login' => '123456', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))->toStartWith('Muitas tentativas');
    $this->assertGuest();
});

test('varredura de códigos a partir do mesmo IP é bloqueada', function () {
    foreach (range(1, 20) as $i) {
        $this->post('/login', ['login' => (string) (500000 + $i), 'password' => 'chute']);
    }

    $this->post('/login', ['login' => '600000', 'password' => 'chute'])->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))->toStartWith('Muitas tentativas');
});

test('o aluno pode sair', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
