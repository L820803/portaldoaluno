<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Senha definitiva: regras padrão (mín. 8 caracteres) e diferente do código
 * do aluno, que é a senha inicial conhecida.
 */
class SenhaDoAluno implements ValidationRule
{
    public function __construct(protected ?User $user = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validador = Validator::make([$attribute => $value], [$attribute => ['string', Password::defaults()]]);

        foreach ($validador->errors()->get($attribute) as $mensagem) {
            $fail($mensagem);
        }

        if ($this->user && (string) $value === (string) $this->user->login) {
            $fail('A nova senha não pode ser igual ao código do aluno.');
        }
    }
}
