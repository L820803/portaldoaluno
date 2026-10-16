<?php

namespace App\Services\ECidade;

use App\Services\ECidade\Exceptions\ECidadeException;
use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\Gateway;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;

/**
 * Acesso aos dados do aluno no e-cidade.
 *
 * Cada método corresponde a uma chamada SOAP usada pelo portal legado.
 * As respostas são arrays (estrutura original do e-cidade, em UTF-8) e ficam
 * em cache conforme config('ecidade.cache.ttl').
 *
 * @throws ECidadeUnavailableException quando o e-cidade não responde
 * @throws ECidadeRequestException quando o e-cidade devolve erro
 */
class ECidade
{
    protected bool $useCache = true;

    /**
     * @param  array<string, int>  $ttl  Segundos por método ("default" para os demais)
     */
    public function __construct(
        protected Gateway $gateway,
        protected Cache $cache,
        protected array $ttl = [],
        protected string $cachePrefix = 'ecidade',
        protected int $pausaAposFalha = 0,
    ) {}

    /**
     * Nome do órgão e logo (base64) — cabeçalho do portal.
     */
    public function dadosPrefeitura(): array
    {
        return $this->call('DadosPrefeitura', ['lprefeitura' => true]);
    }

    /**
     * Dados mínimos para o primeiro acesso (codigo, nome, email). Nunca usa cache.
     */
    public function alunoLoginDados(int|string $codigoAluno): array
    {
        return $this->call('AlunoLoginDados', ['codigo_aluno' => $codigoAluno]);
    }

    /**
     * Dados pessoais, matrículas, documentos, outros dados e escola.
     */
    public function alunoDados(int|string $codigoAluno): array
    {
        return $this->call('AlunoDados', ['codigo_aluno' => $codigoAluno]);
    }

    /**
     * Matrícula, grade de aproveitamento (notas, faltas, pareceres) e atividades complementares/AEE.
     */
    public function matricula(int|string $codigoMatricula): array
    {
        return $this->call('MatriculaAluno', ['codigo_matricula' => $codigoMatricula]);
    }

    public function faltas(int|string $codigoMatricula): array
    {
        return $this->call('FaltasMatriculaAluno', ['codigo_matricula' => $codigoMatricula]);
    }

    public function calendario(int|string $codigoMatricula): array
    {
        return $this->call('CalendarioEscolarAluno', ['codigo_matricula' => $codigoMatricula]);
    }

    public function horarios(int|string $codigoMatricula): array
    {
        return $this->call('HorarioAulaMatriculaAluno', ['codigo_matricula' => $codigoMatricula]);
    }

    public function historico(int|string $codigoAluno): array
    {
        return $this->call('HistoricoAluno', ['codigo_aluno' => $codigoAluno]);
    }

    /**
     * Empréstimos da biblioteca; sem datas o e-cidade usa o período padrão.
     */
    public function biblioteca(int|string $codigoAluno, ?DateTimeInterface $de = null, ?DateTimeInterface $ate = null): array
    {
        return $this->call('AlunoLeitorBiblioteca', [
            'codigo_aluno' => $codigoAluno,
            'data_inicial' => $de?->format('Y-m-d') ?? '',
            'data_final' => $ate?->format('Y-m-d') ?? '',
        ]);
    }

    /**
     * Instância que ignora o cache (consulta e regrava), p.ex. no login.
     */
    public function fresh(): static
    {
        $clone = clone $this;
        $clone->useCache = false;

        return $clone;
    }

    /**
     * Remove do cache a resposta de um método.
     *
     * @param  array<string, mixed>  $params
     */
    public function forget(string $method, array $params): void
    {
        $this->cache->forget($this->cacheKey($method, $params));
    }

    /**
     * Chamada genérica ao webservice, com cache e log.
     *
     * @param  array<string, mixed>  $params
     */
    public function call(string $method, array $params = []): array
    {
        $ttl = $this->ttl[$method] ?? $this->ttl['default'] ?? 0;
        $key = $this->cacheKey($method, $params);

        if ($ttl > 0 && $this->useCache && is_array($cached = $this->cache->get($key))) {
            return $cached;
        }

        $result = $this->request($method, $params);

        if ($ttl > 0) {
            $this->cache->put($key, $result, $ttl);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function request(string $method, array $params): array
    {
        // Disjuntor: depois de uma falha de conexão, não insiste até a pausa acabar
        // (evita que cada página espere o timeout com o e-cidade fora do ar)
        if ($this->pausaAposFalha > 0 && $this->cache->has($this->cachePrefix.':indisponivel')) {
            throw new ECidadeUnavailableException('e-cidade indisponível (aguardando nova tentativa).', $method);
        }

        $start = microtime(true);

        try {
            $result = $this->gateway->call($method, $params);
        } catch (ECidadeException $e) {
            if ($e instanceof ECidadeUnavailableException && $this->pausaAposFalha > 0) {
                $this->cache->put($this->cachePrefix.':indisponivel', true, $this->pausaAposFalha);
            }

            Log::warning('e-cidade: falha em '.$method, [
                'params' => $params,
                'erro' => $e->getMessage(),
                'tipo' => class_basename($e),
                'ms' => $this->elapsed($start),
            ]);

            throw $e;
        }

        Log::debug('e-cidade: '.$method, ['params' => $params, 'ms' => $this->elapsed($start)]);

        // Retornos vazios do e-cidade (null/false/"") viram array vazio
        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function cacheKey(string $method, array $params): string
    {
        ksort($params);

        return $this->cachePrefix.':'.$method.':'.md5(serialize($params));
    }

    protected function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
