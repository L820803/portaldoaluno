<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('visitantes são levados ao login', function () {
    $this->get('/inicio')->assertRedirect('/login');
});

test('usuários autenticados veem a página inicial', function () {
    $this->actingAs(User::factory()->create(['login' => '123456']));

    $this->get('/inicio')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inicio'));
});
