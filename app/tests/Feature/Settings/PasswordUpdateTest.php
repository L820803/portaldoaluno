<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a senha pode ser alterada', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/conta/senha')
        ->put('/conta/senha', [
            'current_password' => 'password',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success')
        ->assertRedirect('/conta/senha');

    expect(Hash::check('nova-senha-123', $user->refresh()->password))->toBeTrue();
});

test('a senha atual correta é obrigatória', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/conta/senha')
        ->put('/conta/senha', [
            'current_password' => 'errada',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/conta/senha');
});

test('a nova senha não pode voltar a ser o código do aluno', function () {
    $user = User::factory()->create(['login' => '12345678']);

    $this->actingAs($user)
        ->put('/conta/senha', [
            'current_password' => 'password',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])
        ->assertSessionHasErrors(['password' => 'A nova senha não pode ser igual ao código do aluno.']);
});
