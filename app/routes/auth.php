<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Conta\ConfirmarEmailController;
use App\Http\Controllers\Conta\PrimeiroAcessoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('esqueci-senha', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('esqueci-senha', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('redefinir-senha/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('redefinir-senha', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

// Link do e-mail de confirmação: funciona logado ou não (assinatura conferida no controller)
Route::get('conta/email/confirmar/{user}', ConfirmarEmailController::class)
    ->whereNumber('user')
    ->middleware('throttle:12,1')
    ->name('email.confirmar');

Route::middleware('auth')->group(function () {
    Route::get('primeiro-acesso', [PrimeiroAcessoController::class, 'show'])
        ->name('primeiro-acesso');

    Route::put('primeiro-acesso/senha', [PrimeiroAcessoController::class, 'senha'])
        ->middleware('throttle:10,1')
        ->name('primeiro-acesso.senha');

    Route::post('primeiro-acesso/email', [PrimeiroAcessoController::class, 'email'])
        ->middleware('throttle:6,1')
        ->name('primeiro-acesso.email');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
