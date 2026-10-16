<?php

use App\Models\User;
use App\Notifications\ConfirmarEmail;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\Gateway;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

// Data de nascimento do aluno nas fixtures (AlunoDados.json)
const NASCIMENTO = '2013-03-21';

beforeEach(function () {
    Notification::fake();
    $this->gateway = app(Gateway::class);
    $this->aluno = User::factory()->primeiroAcesso()->create(['login' => '123456']);
});

/**
 * URL de confirmação enviada no último e-mail para o endereço.
 */
function linkConfirmacao(string $email): string
{
    $url = null;

    Notification::assertSentOnDemand(ConfirmarEmail::class, function (ConfirmarEmail $n, array $canais, AnonymousNotifiable $destino) use ($email, &$url) {
        if ($destino->routes['mail'] === $email) {
            $url = $n->url;
        }

        return true;
    });

    return $url;
}

test('cadastro incompleto bloqueia o portal e leva ao primeiro acesso', function (string $uri) {
    $this->actingAs($this->aluno)->get($uri)->assertRedirect(route('primeiro-acesso'));
})->with(['/inicio', '/boletim', '/conta/perfil', '/conta/senha']);

test('a tela de primeiro acesso começa pela senha', function () {
    $this->actingAs($this->aluno)
        ->get('/primeiro-acesso')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conta/primeiro-acesso')
            ->where('precisaSenha', true)
            ->where('precisaEmail', true)
            ->where('pedirNascimento', true)
            ->where('emailPendente', null));
});

test('cria a senha confirmando a data de nascimento', function () {
    $this->actingAs($this->aluno)
        ->put('/primeiro-acesso/senha', ['nascimento' => NASCIMENTO, 'password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova'])
        ->assertRedirect(route('primeiro-acesso'))
        ->assertSessionHas('success');

    expect(Hash::check('minha-senha-nova', $this->aluno->refresh()->password))->toBeTrue();

    $this->get('/primeiro-acesso')->assertInertia(fn (Assert $page) => $page->where('precisaSenha', false)->where('precisaEmail', true));
});

test('data de nascimento errada não troca a senha', function () {
    $this->actingAs($this->aluno)
        ->put('/primeiro-acesso/senha', ['nascimento' => '2010-01-01', 'password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova'])
        ->assertSessionHasErrors(['nascimento' => 'A data de nascimento não confere com o cadastro da escola.']);

    expect(Hash::check('123456', $this->aluno->refresh()->password))->toBeTrue();
});

test('após 5 datas erradas as tentativas são bloqueadas', function () {
    $this->actingAs($this->aluno);
    $tentativa = ['nascimento' => '2010-01-01', 'password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova'];

    foreach (range(1, 5) as $_) {
        $this->put('/primeiro-acesso/senha', $tentativa);
    }

    // Mesmo com a data certa, aguarda o bloqueio
    $this->put('/primeiro-acesso/senha', [...$tentativa, 'nascimento' => NASCIMENTO])->assertSessionHasErrors('nascimento');
    expect(session('errors')->first('nascimento'))->toStartWith('Muitas tentativas');
});

test('a nova senha não pode ser o código do aluno nem curta', function (string $senha) {
    $this->actingAs($this->aluno)
        ->put('/primeiro-acesso/senha', ['nascimento' => NASCIMENTO, 'password' => $senha, 'password_confirmation' => $senha])
        ->assertSessionHasErrors('password');
})->with(['123456', 'curta']);

test('sem a confirmação de nascimento configurada, basta a nova senha', function () {
    config(['portal.confirmar_nascimento' => false]);

    $this->actingAs($this->aluno)
        ->put('/primeiro-acesso/senha', ['password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova'])
        ->assertSessionHasNoErrors();
});

test('com o e-cidade fora do ar a data não pode ser conferida', function () {
    $this->gateway->respondWith('AlunoDados', new ECidadeUnavailableException('timeout'));

    $this->actingAs($this->aluno)
        ->put('/primeiro-acesso/senha', ['nascimento' => NASCIMENTO, 'password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova'])
        ->assertSessionHasErrors('nascimento');

    expect(Hash::check('123456', $this->aluno->refresh()->password))->toBeTrue();
});

test('o e-mail informado só é gravado depois da confirmação', function () {
    $this->aluno->forceFill(['password' => Hash::make('minha-senha-nova')])->save();

    $this->actingAs($this->aluno)
        ->post('/primeiro-acesso/email', ['email' => 'Familia@Example.com'])
        ->assertSessionHas('success');

    expect($this->aluno->refresh()->email)->toBeNull();
    $this->get('/inicio')->assertRedirect(route('primeiro-acesso'));
    $this->get('/primeiro-acesso')->assertInertia(fn (Assert $page) => $page->where('emailPendente', 'familia@example.com'));

    $this->get(linkConfirmacao('familia@example.com'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($this->aluno->refresh())
        ->email->toBe('familia@example.com')
        ->validated_email->toBeTrue();

    $this->get('/inicio')->assertOk();
    $this->get('/primeiro-acesso')->assertRedirect(route('dashboard'));
});

test('o link de confirmação funciona sem estar logado', function () {
    $this->aluno->forceFill(['password' => Hash::make('minha-senha-nova')])->save();
    $this->actingAs($this->aluno)->post('/primeiro-acesso/email', ['email' => 'familia@example.com']);
    $link = linkConfirmacao('familia@example.com');

    auth()->logout();

    $this->get($link)->assertRedirect(route('login'))->assertSessionHas('status');
    expect($this->aluno->refresh()->email)->toBe('familia@example.com');
});

test('link expirado ou adulterado não altera o e-mail', function () {
    $this->aluno->forceFill(['password' => Hash::make('minha-senha-nova')])->save();
    $this->actingAs($this->aluno)->post('/primeiro-acesso/email', ['email' => 'familia@example.com']);
    $link = linkConfirmacao('familia@example.com');

    $this->get(str_replace('familia%40example.com', 'invasor%40example.com', $link))->assertSessionHas('error');

    $this->travel(25)->hours();
    $this->get($link)->assertSessionHas('error');

    expect($this->aluno->refresh()->email)->toBeNull();
});

test('link antigo deixa de valer quando outro e-mail é confirmado', function () {
    $this->aluno->forceFill(['password' => Hash::make('minha-senha-nova')])->save();
    $this->actingAs($this->aluno);

    $this->post('/primeiro-acesso/email', ['email' => 'primeiro@example.com']);
    $antigo = linkConfirmacao('primeiro@example.com');
    $this->post('/primeiro-acesso/email', ['email' => 'segundo@example.com']);
    $novo = linkConfirmacao('segundo@example.com');

    $this->get($novo);
    $this->get($antigo)->assertSessionHas('error');

    expect($this->aluno->refresh()->email)->toBe('segundo@example.com');
});

test('aluno com e-mail vindo da escola só precisa trocar a senha', function () {
    $aluno = User::factory()->primeiroAcesso()->create(['login' => '222222', 'email' => 'escola@example.com']);

    $this->actingAs($aluno)
        ->put('/primeiro-acesso/senha', ['nascimento' => NASCIMENTO, 'password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova'])
        ->assertRedirect(route('primeiro-acesso'));

    $this->get('/primeiro-acesso')->assertRedirect(route('dashboard'));
    $this->get('/inicio')->assertOk();
});

test('quem já completou o cadastro não usa as rotas do primeiro acesso', function () {
    $completo = User::factory()->create(['login' => '333333']);

    $this->actingAs($completo)->get('/primeiro-acesso')->assertRedirect(route('dashboard'));
    $this->put('/primeiro-acesso/senha', ['password' => 'x', 'password_confirmation' => 'x'])->assertForbidden();
    $this->post('/primeiro-acesso/email', ['email' => 'x@example.com'])->assertForbidden();
});

test('usuário administrativo não passa pelo primeiro acesso', function () {
    $admin = User::factory()->create(['login' => 'admin', 'email' => null]);

    $this->actingAs($admin)->get('/inicio')->assertOk();
});

test('o e-mail de confirmação é enviado ao novo endereço com o link', function () {
    $this->aluno->forceFill(['password' => Hash::make('minha-senha-nova')])->save();
    $this->actingAs($this->aluno)->post('/primeiro-acesso/email', ['email' => 'familia@example.com']);

    $email = (new ConfirmarEmail($this->aluno, linkConfirmacao('familia@example.com'), 24))->toMail(new AnonymousNotifiable);

    expect($email->subject)->toBe('Confirme seu e-mail - Portal do Aluno')
        ->and($email->actionText)->toBe('Confirmar e-mail')
        ->and($email->greeting)->toContain('Olá');
});

test('link de confirmação com identificador inválido responde 404', function () {
    $this->get('/conta/email/confirmar/abc')->assertNotFound();
});
