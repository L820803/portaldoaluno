<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ECidade\Exceptions\ECidadeException;
use App\Services\Portal\AlunoAtual;
use Closure;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Base das telas do aluno que dependem do e-cidade: a página sempre abre;
 * sem e-cidade, recebe erro = true e mostra o aviso com "tentar novamente".
 */
abstract class TelaController extends Controller
{
    public function __construct(protected AlunoAtual $aluno) {}

    /**
     * @param  array<string, mixed>  $extra  Props adicionais (filtros, etc.)
     */
    protected function tela(string $componente, Closure $consulta, array $extra = []): Response
    {
        $dados = null;
        $erro = false;

        if ($this->aluno->ehAluno()) {
            try {
                $dados = $consulta();
            } catch (ECidadeException) {
                $erro = true;
            }
        }

        return Inertia::render($componente, ['dados' => $dados, 'erro' => $erro, ...$extra]);
    }
}
