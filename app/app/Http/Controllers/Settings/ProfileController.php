<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\Portal\ContaAluno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(protected ContaAluno $conta) {}

    /**
     * Perfil: nome e código (do e-cidade) e o e-mail de contato.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'login' => $user->login,
            'email' => $user->email,
            'emailConfirmado' => (bool) $user->validated_email,
            'emailPendente' => $this->conta->emailPendente($user),
        ]);
    }

    /**
     * Pede a troca (ou a confirmação) do e-mail. O e-mail só muda depois que
     * o aluno clica no link enviado ao novo endereço.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $email = mb_strtolower($request->validated('email'));

        if ($email === mb_strtolower((string) $user->email) && $user->validated_email) {
            return back()->with('info', 'Esse já é o seu e-mail confirmado.');
        }

        $this->conta->solicitarTrocaEmail($user, $email);

        return back()->with('success', "Enviamos um link de confirmação para {$email}. O e-mail só será alterado depois que você clicar nele.");
    }
}
