<?php

namespace App\Services\ECidade\Exceptions;

use RuntimeException;

/**
 * Erro em uma chamada ao webservice do e-cidade.
 */
class ECidadeException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $method = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
