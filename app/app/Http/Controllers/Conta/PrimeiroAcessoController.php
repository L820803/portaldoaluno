<?php

namespace App\Http\Controllers\Conta;

use App\Http\Controllers\Controller;
use App\Rules\SenhaDoAluno;
use App\Services\ECidade\Exceptions\ECidadeException;
use App\Services\Portal\ContaAluno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Primeiro acesso: criar a senha definitiva e informar o e-mail.
 */
class PrimeiroAcessoController extends Controller
{
    /** Tentativas de data de nascimento a cada 15 minutos */
    protected const TENTATIVAS_NASCIMENTO = 5;

    public function __construct(protected ContaAluno $conta) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! ContaAluno::ehAluno($user) || $this->conta->cadastroCompleto($user)) {
            return redirect()->route('dashboard');
        }

        $precisaSenha = $this->conta->usaSenhaInicial($user);

        return Inertia::render('conta/primeiro-acesso', [
            'precisaSenha' => $precisaSenha,
            'precisaEmail' => $this->conta->precisaEmail($user),
            'emailPendente' => $this->conta->emailPendente($user),
            'pedirNascimento' => $precisaSenha && config('portal.confirmar_nascimento'),
        ]);
    }

    /**
     * Troca a senha inicial (= código do aluno) pela senha definitiva.
     */
    public function senha(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->conta->usaSenhaInicial($user), 403);

        $pedirNascimento = (bool) config('portal.confirmar_nascimento');

        $dados = $request->validate([
            'nascimento' => [$pedirNascimento ? 'required' : 'nullable', 'date_format:Y-m-d'],
            'password' => ['required', 'confirmed', new SenhaDoAluno($user)],
        ], attributes: ['nascimento' => 'data de nascimento', 'password' => 'nova senha']);

        if ($pedirNascimento) {
            $this->conferirNascimento($user, $dados['nascimento']);
        }

        $user->forceFill(['password' => Hash::make($dados['password'])])->save();

        return redirect()->route('primeiro-acesso')->with('success', 'Senha criada! Use-a nos próximos acessos.');
    }

    /**
     * Envia (ou reenvia) o link de confirmação do e-mail.
     */
    public function email(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->conta->precisaEmail($user), 403);

        $dados = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], attributes: ['email' => 'e-mail']);

        $this->conta->solicitarTrocaEmail($user, $dados['email']);

        return back()->with('success', 'Enviamos o link de confirmação. Confira sua caixa de entrada (e o spam).');
    }

    /**
     * @throws ValidationException
     */
    protected function conferirNascimento($user, string $informada): void
    {
        $chave = 'primeiro-acesso-nascimento|'.$user->id;

        if (RateLimiter::tooManyAttempts($chave, self::TENTATIVAS_NASCIMENTO)) {
            throw ValidationException::withMessages([
                'nascimento' => 'Muitas tentativas. Tente novamente em '.ceil(RateLimiter::availableIn($chave) / 60).' minutos.',
            ]);
        }

        try {
            $correta = $this->conta->dataNascimento($user);
        } catch (ECidadeException) {
            throw ValidationException::withMessages([
                'nascimento' => 'Não foi possível conferir a data agora: o sistema da Secretaria de Educação não respondeu. Tente novamente em alguns minutos.',
            ]);
        }

        // Sem data no e-cidade não há o que conferir
        if ($correta !== null && $correta !== $informada) {
            RateLimiter::hit($chave, 15 * 60);

            throw ValidationException::withMessages([
                'nascimento' => 'A data de nascimento não confere com o cadastro da escola.',
            ]);
        }

        RateLimiter::clear($chave);
    }
}
