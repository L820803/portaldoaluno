<?php

namespace App\Services\ECidade\Gateways;

use App\Services\ECidade\Exceptions\ECidadeRequestException;
use Closure;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * Simula o e-cidade com respostas fixas.
 *
 * Por padrão responde com database/fixtures/ecidade/{Metodo}.json. Respostas
 * podem ser sobrescritas por método com um valor, uma exceção ou uma closure
 * que recebe os parâmetros.
 */
class FakeGateway implements Gateway
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    protected array $calls = [];

    /**
     * @param  array<string, mixed>  $responses
     */
    public function __construct(
        protected ?string $fixturesPath = null,
        protected array $responses = [],
    ) {}

    public function call(string $method, array $params = []): mixed
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        if (array_key_exists($method, $this->responses)) {
            $response = $this->responses[$method];

            if ($response instanceof Throwable) {
                throw $response;
            }

            return $response instanceof Closure ? $response($params) : $response;
        }

        $file = $this->fixturesPath ? rtrim($this->fixturesPath, '/')."/{$method}.json" : null;

        if (! $file || ! is_file($file)) {
            throw new ECidadeRequestException("Método {$method} sem resposta simulada.", $method);
        }

        return json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Define a resposta de um método.
     */
    public function respondWith(string $method, mixed $response): static
    {
        $this->responses[$method] = $response;

        return $this;
    }

    /**
     * @return list<array{method: string, params: array<string, mixed>}>
     */
    public function calls(?string $method = null): array
    {
        return array_values(array_filter(
            $this->calls,
            fn (array $call) => $method === null || $call['method'] === $method,
        ));
    }

    public function assertCalled(string $method, ?array $params = null, int $times = 1): void
    {
        $calls = array_filter(
            $this->calls($method),
            fn (array $call) => $params === null || $call['params'] == $params,
        );

        Assert::assertCount($times, $calls, "Esperava {$times} chamada(s) a {$method}, houve ".count($calls).'.');
    }

    public function assertNotCalled(string $method): void
    {
        $this->assertCalled($method, times: 0);
    }
}
