<?php

namespace App\Notifications;

use Illuminate\Support\Str;

/**
 * Textos comuns dos e-mails.
 */
class Saudacao
{
    /**
     * "MARIA EDUARDA DA SILVA" → "Maria".
     */
    public static function primeiroNome(?string $nome): string
    {
        $primeiro = Str::before(trim((string) $nome), ' ');

        return $primeiro === '' ? 'aluno(a)' : Str::ucfirst(mb_strtolower($primeiro));
    }

    public static function assinatura(): string
    {
        return "Atenciosamente,  \nPortal do Aluno - ".config('portal.orgao');
    }
}
