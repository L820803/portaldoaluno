<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\SenhaDoAluno;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Nova senha pelo link do e-mail (alterarSenha no legado).
 */
class NewPasswordController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/reset-password', [
            'login' => (string) $request->query('login'),
            'token' => $request->route('token'),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'login' => ['required', 'string'],
            'password' => ['required', 'confirmed', new SenhaDoAluno(User::porLogin((string) $request->input('login')))],
        ], attributes: ['password' => 'nova senha']);

        $status = Password::reset(
            $request->only('login', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    // Quem recebeu o link comprovou que o e-mail é dele
                    'validated_email' => true,
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            return to_route('login')->with('status', 'Senha redefinida! Entre com a nova senha.');
        }

        throw ValidationException::withMessages([
            'password' => 'Este link expirou ou já foi usado. Peça um novo em "Esqueci minha senha".',
        ]);
    }
}
