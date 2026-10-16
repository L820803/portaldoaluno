<?php

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use App\Notifications\RedefinirSenha;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
});

function tokenEnviado(User $user): string
{
    $token = null;

    Notification::assertSentTo($user, RedefinirSenha::class, function (RedefinirSenha $n) use (&$token) {
        $token = $n->token;

        return true;
    });

    return $token;
}

test('a tela de esqueci minha senha abre', function () {
    $this->get('/esqueci-senha')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/forgot-password'));
});

test('o link é pedido pelo código do aluno e o e-mail aparece mascarado', function () {
    $user = User::factory()->create(['login' => '123456', 'email' => 'maria.souza@gmail.com']);

    $this->post('/esqueci-senha', ['login' => '123456'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', fn (string $s) => str_contains($s, 'ma*********@gm***.com') && ! str_contains($s, 'maria.souza'));

    Notification::assertSentTo($user, RedefinirSenha::class);
});

test('código sem cadastro no portal orienta o primeiro acesso', function () {
    $this->post('/esqueci-senha', ['login' => '999999'])->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))->toContain('primeiro acesso');
    Notification::assertNothingSent();
});

test('aluno sem e-mail é orientado a procurar a secretaria', function () {
    User::factory()->create(['login' => '123456', 'email' => null]);

    $this->post('/esqueci-senha', ['login' => '123456'])->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))->toContain('secretaria');
    Notification::assertNothingSent();
});

test('pedidos repetidos em menos de um minuto são recusados', function () {
    User::factory()->create(['login' => '123456']);

    $this->post('/esqueci-senha', ['login' => '123456'])->assertSessionHasNoErrors();
    $this->post('/esqueci-senha', ['login' => '123456'])->assertSessionHasErrors('login');
});

test('a tela de nova senha recebe o token e o código', function () {
    $user = User::factory()->create(['login' => '123456']);
    $this->post('/esqueci-senha', ['login' => '123456']);
    $token = tokenEnviado($user);

    $this->get("/redefinir-senha/{$token}?login=123456")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/reset-password')->where('token', $token)->where('login', '123456'));
});

test('a senha é redefinida com um token válido e o e-mail passa a ser confirmado', function () {
    $user = User::factory()->unverified()->create(['login' => '123456']);
    $this->post('/esqueci-senha', ['login' => '123456']);

    $this->post('/redefinir-senha', [
        'token' => tokenEnviado($user),
        'login' => '123456',
        'password' => 'senha-redefinida',
        'password_confirmation' => 'senha-redefinida',
    ])->assertRedirect(route('login'))->assertSessionHas('status');

    expect($user->refresh())
        ->validated_email->toBeTrue()
        ->and(Hash::check('senha-redefinida', $user->password))->toBeTrue();
});

test('o token só vale uma vez', function () {
    $user = User::factory()->create(['login' => '123456']);
    $this->post('/esqueci-senha', ['login' => '123456']);
    $dados = ['token' => tokenEnviado($user), 'login' => '123456', 'password' => 'senha-redefinida', 'password_confirmation' => 'senha-redefinida'];

    $this->post('/redefinir-senha', $dados)->assertSessionHasNoErrors();
    $this->post('/redefinir-senha', $dados)->assertSessionHasErrors('password');
});

test('token inválido não redefine a senha', function () {
    $user = User::factory()->create(['login' => '123456']);

    $this->post('/redefinir-senha', ['token' => 'invalido', 'login' => '123456', 'password' => 'senha-redefinida', 'password_confirmation' => 'senha-redefinida'])
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('a nova senha não pode ser o código do aluno', function () {
    $user = User::factory()->create(['login' => '12345678']);
    $this->post('/esqueci-senha', ['login' => '12345678']);

    $this->post('/redefinir-senha', ['token' => tokenEnviado($user), 'login' => '12345678', 'password' => '12345678', 'password_confirmation' => '12345678'])
        ->assertSessionHasErrors('password');
});

test('irmãos com o mesmo e-mail do responsável redefinem a senha de forma independente', function () {
    $irmao1 = User::factory()->create(['login' => '111111', 'email' => 'responsavel@example.com']);
    $irmao2 = User::factory()->create(['login' => '222222', 'email' => 'responsavel@example.com']);

    $this->post('/esqueci-senha', ['login' => '111111']);
    $this->post('/esqueci-senha', ['login' => '222222']);

    $this->post('/redefinir-senha', ['token' => tokenEnviado($irmao1), 'login' => '111111', 'password' => 'senha-do-irmao-1', 'password_confirmation' => 'senha-do-irmao-1'])
        ->assertSessionHasNoErrors();
    $this->post('/redefinir-senha', ['token' => tokenEnviado($irmao2), 'login' => '222222', 'password' => 'senha-do-irmao-2', 'password_confirmation' => 'senha-do-irmao-2'])
        ->assertSessionHasNoErrors();

    expect(Hash::check('senha-do-irmao-1', $irmao1->refresh()->password))->toBeTrue()
        ->and(Hash::check('senha-do-irmao-2', $irmao2->refresh()->password))->toBeTrue();
});

test('o token de um irmão não serve para o outro', function () {
    $irmao1 = User::factory()->create(['login' => '111111', 'email' => 'responsavel@example.com']);
    User::factory()->create(['login' => '222222', 'email' => 'responsavel@example.com']);

    $this->post('/esqueci-senha', ['login' => '111111']);

    $this->post('/redefinir-senha', ['token' => tokenEnviado($irmao1), 'login' => '222222', 'password' => 'tentativa-123', 'password_confirmation' => 'tentativa-123'])
        ->assertSessionHasErrors('password');
});

test('o e-mail de redefinição leva ao link com o código do aluno', function () {
    $user = User::factory()->create(['login' => '123456', 'name' => 'MARIA EDUARDA']);

    $email = (new RedefinirSenha('abc'))->toMail($user);

    expect($email->actionUrl)->toBe(route('password.reset', ['token' => 'abc', 'login' => '123456']))
        ->and($email->greeting)->toBe('Olá, Maria!')
        ->and($email->subject)->toBe('Redefinição de senha - Portal do Aluno');
});

test('mascara de e-mail', function (string $email, string $esperado) {
    expect(PasswordResetLinkController::mascarar($email))->toBe($esperado);
})->with([
    ['maria.souza@gmail.com', 'ma*********@gm***.com'],
    ['ab@x.com.br', 'ab***@x.***.br'],
]);
