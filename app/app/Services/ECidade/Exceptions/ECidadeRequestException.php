<?php

namespace App\Services\ECidade\Exceptions;

/**
 * O e-cidade respondeu com erro de negócio (ex.: "Aluno não encontrado").
 * A mensagem vem do próprio e-cidade e pode ser exibida ao usuário,
 * como o legado fazia no login.
 */
class ECidadeRequestException extends ECidadeException
{
    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
