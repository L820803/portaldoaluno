<?php

namespace App\Http\Middleware;

use App\Services\Portal\ContaAluno;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aluno com a senha inicial ou sem e-mail vai para o primeiro acesso
 * ("Seu cadastro no sistema está incompleto" no legado).
 */
class EnsureCadastroCompleto
{
    public function __construct(protected ContaAluno $conta) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ContaAluno::ehAluno($user) && ! $this->conta->cadastroCompleto($user)) {
            abort_if($request->expectsJson(), 403, 'Cadastro incompleto.');

            return redirect()->route('primeiro-acesso');
        }

        return $next($request);
    }
}
