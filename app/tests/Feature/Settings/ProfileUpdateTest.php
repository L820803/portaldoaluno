<?php

use App\Models\User;
use App\Notifications\ConfirmarEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->user = User::factory()->create(['login' => '123456', 'email' => 'atual@example.com']);
});

test('a página de perfil mostra o código e a situação do e-mail', function () {
    $this->actingAs($this->user)
        ->get('/conta/perfil')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->where('login', '123456')
            ->where('email', 'atual@example.com')
            ->where('emailConfirmado', true)
            ->where('emailPendente', null));
});

test('trocar o e-mail envia confirmação e só altera depois do clique', function () {
    $this->actingAs($this->user)
        ->patch('/conta/perfil', ['email' => 'Novo@Example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect($this->user->refresh()->email)->toBe('atual@example.com');

    Notification::assertSentOnDemand(ConfirmarEmail::class, fn ($n, $c, AnonymousNotifiable $destino) => $destino->routes['mail'] === 'novo@example.com');

    $this->get('/conta/perfil')->assertInertia(fn (Assert $page) => $page->where('emailPendente', 'novo@example.com'));
});

test('o nome não pode ser alterado pelo aluno', function () {
    $this->actingAs($this->user)->patch('/conta/perfil', ['email' => 'atual@example.com', 'name' => 'OUTRO NOME']);

    expect($this->user->refresh()->name)->not->toBe('OUTRO NOME');
});

test('reenviar o mesmo e-mail já confirmado não envia nada', function () {
    $this->actingAs($this->user)
        ->patch('/conta/perfil', ['email' => 'atual@example.com'])
        ->assertSessionHas('info');

    Notification::assertNothingSent();
});

test('e-mail vindo da escola (não confirmado) pode ser confirmado', function () {
    $this->user->forceFill(['validated_email' => false])->save();

    $this->actingAs($this->user)->patch('/conta/perfil', ['email' => 'atual@example.com'])->assertSessionHas('success');

    Notification::assertSentOnDemand(ConfirmarEmail::class);
});

test('irmãos podem usar o mesmo e-mail do responsável', function () {
    User::factory()->create(['login' => '654321', 'email' => 'responsavel@example.com']);

    $this->actingAs($this->user)
        ->patch('/conta/perfil', ['email' => 'responsavel@example.com'])
        ->assertSessionHasNoErrors();
});

test('e-mail inválido é rejeitado', function () {
    $this->actingAs($this->user)
        ->patch('/conta/perfil', ['email' => 'nao-e-email'])
        ->assertSessionHasErrors('email');
});
