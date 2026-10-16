<?php

use App\Models\User;

test('visitante na raiz vai para o login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('usuário autenticado na raiz vai para o painel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('cadastro público e exclusão de conta não existem', function () {
    $this->get('/register')->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->delete('/settings/profile')
        ->assertStatus(405);
});
