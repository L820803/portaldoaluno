<?php

use App\Models\User;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('página inexistente mostra a página de erro 404', function () {
    $this->get('/nao-existe')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('erro')->where('status', 404));
});

test('no 404 o usuário logado continua reconhecido', function () {
    $this->actingAs(User::factory()->create(['login' => '123456']))
        ->get('/nao-existe')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('erro')->has('auth.user'));
});

test('e-cidade indisponível mostra página própria com status 503', function () {
    Route::middleware('web')->get('/_teste/ecidade', fn () => throw new ECidadeUnavailableException('timeout'));

    $this->actingAs(User::factory()->create())
        ->get('/_teste/ecidade')
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page->component('erro')->where('status', 503)->where('ecidade', true));
});

test('acesso negado mostra a página de erro 403', function () {
    Route::middleware('web')->get('/_teste/negado', fn () => abort(403));

    $this->get('/_teste/negado')
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('erro')->where('status', 403));
});

test('erro 500 mostra a página amigável quando o debug está desligado', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/_teste/falha', fn () => throw new RuntimeException('boom'));

    $this->get('/_teste/falha')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page->component('erro')->where('status', 500));
});

test('requisições JSON continuam recebendo JSON', function () {
    $this->getJson('/nao-existe')->assertNotFound()->assertJsonStructure(['message']);
});
