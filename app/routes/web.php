<?php

use App\Http\Controllers\Portal\FichaController;
use App\Http\Controllers\Portal\ImagemController;
use App\Http\Controllers\Portal\InicioController;
use App\Http\Controllers\Portal\MatriculaAtivaController;
use App\Http\Controllers\Portal\VidaEscolarController;
use Illuminate\Support\Facades\Route;

// Sem página pública: o portal começa no login (ou no início, se já logado)
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

// Logo da prefeitura (também usado na tela de login)
Route::get('prefeitura/logo', [ImagemController::class, 'logo'])->name('prefeitura.logo');

// Foto do aluno: também aparece no primeiro acesso, antes do cadastro completo
Route::get('aluno/foto', [ImagemController::class, 'foto'])->middleware('auth')->name('aluno.foto');

Route::middleware(['auth', 'cadastro'])->group(function () {
    Route::get('inicio', InicioController::class)->name('dashboard');

    Route::get('meus-dados', [FichaController::class, 'meusDados'])->name('meus-dados');
    Route::get('documentos', [FichaController::class, 'documentos'])->name('documentos');
    Route::get('outras-informacoes', [FichaController::class, 'outrasInformacoes'])->name('outras-informacoes');
    Route::get('minha-escola', [FichaController::class, 'minhaEscola'])->name('minha-escola');

    Route::put('matricula-ativa', [MatriculaAtivaController::class, 'update'])->name('matricula.ativa');

    Route::get('boletim', [VidaEscolarController::class, 'boletim'])->name('boletim');
    Route::get('boletim/parecer/{regencia}/{periodo}', [VidaEscolarController::class, 'parecer'])
        ->whereNumber(['regencia', 'periodo'])
        ->name('boletim.parecer');
    Route::get('faltas', [VidaEscolarController::class, 'faltas'])->name('faltas');
    Route::get('historico', [VidaEscolarController::class, 'historico'])->name('historico');
    Route::get('horarios', [VidaEscolarController::class, 'horarios'])->name('horarios');
    Route::get('calendario', [VidaEscolarController::class, 'calendario'])->name('calendario');
    Route::get('biblioteca', [VidaEscolarController::class, 'biblioteca'])->name('biblioteca');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// 404 dentro do grupo "web": a página de erro sabe se o usuário está logado
Route::fallback(fn () => abort(404));
