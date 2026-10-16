<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Esqueci minha senha" pelo código do aluno (recuperarSenha no legado).
 */
class PasswordResetLinkController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'login' => ['required', 'string', 'max:100'],
        ], attributes: ['login' => 'código do aluno']);

        $login = trim($dados['login']);
        $user = User::porLogin($login);

        if (! $user) {
            return back()->withErrors(['login' => ctype_digit($login)
                ? 'Ainda não há cadastro no portal para este código. No primeiro acesso, entre usando o código do aluno também como senha.'
                : 'Usuário não encontrado.',
            ])->onlyInput('login');
        }

        if (trim((string) $user->email) === '') {
            return back()->withErrors([
                'login' => 'Não há e-mail cadastrado para este aluno. Procure a secretaria da sua escola.',
            ])->onlyInput('login');
        }

        $status = Password::sendResetLink(['login' => $user->login]);

        if ($status === Password::ResetThrottled) {
            return back()->withErrors(['login' => 'Já enviamos um link há pouco. Aguarde um minuto antes de pedir outro.'])->onlyInput('login');
        }

        $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return back()->with('status', 'Enviamos um link para '.self::mascarar($user->email).". Ele vale por {$minutos} minutos.");
    }

    /**
     * "maria.souza@gmail.com" → "ma*********@gm***.com": o aluno reconhece o
     * endereço sem que ele seja revelado a quem digitou o código.
     */
    public static function mascarar(string $email): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $email, 2), 2, '');
        $ponto = strrpos($dominio, '.');
        $nome = $ponto === false ? $dominio : substr($dominio, 0, $ponto);
        $tld = $ponto === false ? '' : substr($dominio, $ponto);

        $parcial = fn (string $s, int $visiveis) => Str::substr($s, 0, $visiveis).str_repeat('*', max(Str::length($s) - $visiveis, 3));

        return $parcial($usuario, 2).'@'.$parcial($nome, 2).$tld;
    }
}
