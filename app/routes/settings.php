<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// "Minha conta"
Route::middleware(['auth', 'cadastro'])->group(function () {
    Route::redirect('conta', 'conta/perfil');

    Route::get('conta/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('conta/perfil', [ProfileController::class, 'update'])->middleware('throttle:6,1')->name('profile.update');

    Route::get('conta/senha', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('conta/senha', [PasswordController::class, 'update'])->middleware('throttle:10,1')->name('password.update');

    Route::get('conta/aparencia', fn () => Inertia::render('settings/appearance'))->name('appearance');
});
