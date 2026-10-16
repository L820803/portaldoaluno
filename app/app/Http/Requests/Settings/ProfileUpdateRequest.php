<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * O nome vem do e-cidade; o aluno altera apenas o e-mail. Sem "unique":
     * irmãos podem usar o mesmo e-mail do responsável.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'e-mail'];
    }
}
