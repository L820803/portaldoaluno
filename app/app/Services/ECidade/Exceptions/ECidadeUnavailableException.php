<?php

namespace App\Services\ECidade\Exceptions;

/**
 * O e-cidade não respondeu (fora do ar, timeout, falha de rede/SSL).
 */
class ECidadeUnavailableException extends ECidadeException
{
    public function userMessage(): string
    {
        return 'O sistema da Secretaria de Educação está indisponível no momento. Tente novamente em alguns minutos.';
    }
}
