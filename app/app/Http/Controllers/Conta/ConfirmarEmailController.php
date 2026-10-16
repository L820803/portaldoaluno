<?php

namespace App\Http\Controllers\Conta;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Portal\ContaAluno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Link do e-mail de confirmação (ativarConta no legado). Não exige login:
 * o link costuma ser aberto no aplicativo de e-mail do celular.
 */
class ConfirmarEmailController extends Controller
{
    public function __invoke(Request $request, ContaAluno $conta, int $user): RedirectResponse
    {
        $destino = fn () => $request->user() ? redirect()->route('dashboard') : redirect()->route('login');
        $aluno = User::find($user);

        if (! $request->hasValidSignature() || ! $aluno) {
            return $destino()->with('error', 'Este link de confirmação expirou ou é inválido. Peça um novo em "Minha conta".');
        }

        if (! $conta->confirmarEmail($aluno, (string) $request->query('email'), (string) $request->query('atual'))) {
            return $destino()->with('error', 'Este link de confirmação não é mais válido: o e-mail foi alterado depois que ele foi enviado.');
        }

        $mensagem = 'E-mail confirmado! Ele será usado para recuperar sua senha.';

        return $request->user()?->is($aluno)
            ? redirect()->route('dashboard')->with('success', $mensagem)
            : redirect()->route('login')->with('status', $mensagem.' Entre para continuar.');
    }
}
