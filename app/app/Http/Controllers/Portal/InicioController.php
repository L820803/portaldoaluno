<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ECidade\Exceptions\ECidadeException;
use App\Services\Portal\AlunoAtual;
use App\Services\Portal\PainelAluno;
use Closure;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página inicial. Cada cartão é uma prop adiada (grupo próprio): a página
 * abre na hora e os cartões chegam em paralelo; se o e-cidade falhar num
 * deles, só aquele cartão mostra o erro.
 */
class InicioController extends Controller
{
    public function __invoke(AlunoAtual $aluno, PainelAluno $painel): Response
    {
        if (! $aluno->ehAluno()) {
            return Inertia::render('inicio');
        }

        return Inertia::render('inicio', [
            'matricula' => Inertia::defer(fn () => self::seguro(fn () => $painel->matricula()), 'matricula'),
            'aulas' => Inertia::defer(fn () => self::seguro(fn () => $painel->aulas()), 'aulas'),
            'emprestimos' => Inertia::defer(fn () => self::seguro(fn () => ['itens' => $painel->emprestimos()]), 'biblioteca'),
        ]);
    }

    /**
     * Erro do e-cidade vira {erro: true} em vez de derrubar a página.
     */
    protected static function seguro(Closure $consulta): ?array
    {
        try {
            return $consulta();
        } catch (ECidadeException) {
            return ['erro' => true];
        }
    }
}
