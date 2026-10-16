<?php

namespace App\Http\Requests\Auth;

use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\Portal\ContaAluno;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Tentativas por usuário (login + IP) a cada minuto */
    protected const TENTATIVAS_USUARIO = 5;

    /** Tentativas por IP a cada minuto, contra varredura de códigos */
    protected const TENTATIVAS_IP = 20;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['login' => 'código do aluno', 'password' => 'senha'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['login' => trim((string) $this->input('login'))]);
    }

    /**
     * Autentica; no primeiro acesso cria o usuário a partir do e-cidade.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = (string) $this->input('login');
        $senha = (string) $this->input('password');

        try {
            app(ContaAluno::class)->criarNoPrimeiroAcesso($login, $senha);
        } catch (ECidadeUnavailableException $e) {
            throw ValidationException::withMessages(['login' => $e->userMessage()]);
        } catch (ECidadeRequestException $e) {
            // Código que não é de aluno: mesma mensagem de senha errada (não revela quais códigos existem)
            Log::info('Primeiro acesso recusado pelo e-cidade', ['login' => $login, 'erro' => $e->getMessage()]);
        }

        // Sem "lembrar de mim": a tabela legada não tem remember_token
        if (! Auth::attempt(['login' => $login, 'password' => $senha])) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->ipThrottleKey());

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $chave = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), self::TENTATIVAS_USUARIO) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->ipThrottleKey(), self::TENTATIVAS_IP) => $this->ipThrottleKey(),
            default => null,
        };

        if ($chave === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($chave);

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }

    protected function ipThrottleKey(): string
    {
        return 'login-ip|'.$this->ip();
    }
}
