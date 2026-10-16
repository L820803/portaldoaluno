<?php

namespace App\Services\ECidade\Gateways;

use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;

/**
 * Transporte das chamadas ao e-cidade (equivalente ao Soap->processar() do legado).
 */
interface Gateway
{
    /**
     * Executa um método do webservice e devolve a resposta como array UTF-8.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws ECidadeUnavailableException
     * @throws ECidadeRequestException
     */
    public function call(string $method, array $params = []): mixed;
}
