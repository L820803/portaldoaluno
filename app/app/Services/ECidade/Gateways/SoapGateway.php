<?php

namespace App\Services\ECidade\Gateways;

use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use Closure;
use SoapClient;
use SoapFault;

/**
 * Chama o webservice do e-cidade (SOAP sem WSDL, método único "processar").
 *
 * Diferente do legado, o SoapClient não recebe a opção "encoding": assim as
 * strings já chegam em UTF-8 (a opção só convertia o retorno para ISO-8859-1).
 */
class SoapGateway implements Gateway
{
    protected ?SoapClient $client = null;

    /**
     * @param  (Closure(array<string, mixed>): SoapClient)|null  $clientFactory  Permite injetar o cliente nos testes
     */
    public function __construct(
        protected string $uri,
        protected string $location,
        protected int $timeout = 20,
        protected bool $verifySsl = true,
        protected ?Closure $clientFactory = null,
    ) {}

    public function call(string $method, array $params = []): mixed
    {
        try {
            $result = $this->client()->__soapCall('processar', [$method, $params]);
        } catch (SoapFault $e) {
            $message = self::toUtf8((string) $e->getMessage());

            // faultcode "HTTP" = falha de transporte (conexão, timeout, SSL)
            if ($e->faultcode === 'HTTP') {
                throw new ECidadeUnavailableException($message, $method, $e);
            }

            throw new ECidadeRequestException($message, $method, $e);
        }

        return self::normalize($result);
    }

    protected function client(): SoapClient
    {
        if ($this->client) {
            return $this->client;
        }

        if ($this->uri === '' || $this->location === '') {
            throw new ECidadeUnavailableException('Webservice do e-cidade não configurado (ECIDADE_SOAP_URI).');
        }

        $options = [
            'uri' => $this->uri,
            'location' => $this->location,
            'exceptions' => true,
            'connection_timeout' => $this->timeout,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => stream_context_create([
                'http' => ['timeout' => $this->timeout],
                'ssl' => [
                    'verify_peer' => $this->verifySsl,
                    'verify_peer_name' => $this->verifySsl,
                ],
            ]),
        ];

        try {
            return $this->client = $this->clientFactory
                ? ($this->clientFactory)($options)
                : new SoapClient(null, $options);
        } catch (SoapFault $e) {
            throw new ECidadeUnavailableException(self::toUtf8($e->getMessage()), '', $e);
        }
    }

    /**
     * Converte objetos (stdClass) em arrays associativos, recursivamente,
     * garantindo strings UTF-8.
     */
    public static function normalize(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (is_array($value)) {
            return array_map(self::normalize(...), $value);
        }

        return is_string($value) ? self::toUtf8($value) : $value;
    }

    protected static function toUtf8(string $value): string
    {
        return mb_check_encoding($value, 'UTF-8')
            ? $value
            : mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
}
