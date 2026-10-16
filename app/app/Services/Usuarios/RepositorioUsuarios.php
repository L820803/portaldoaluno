<?php

namespace App\Services\Usuarios;

use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Usuários do portal sem banco de dados.
 *
 * Com $arquivo, os usuários ficam num JSON (criado na primeira leitura a partir
 * de $semente, cujas senhas estão em texto puro no campo "senha"). Sem $arquivo
 * (testes), ficam só em memória e começam vazios.
 */
class RepositorioUsuarios
{
    /** @var array<int, array<string, mixed>> */
    protected array $memoria = [];

    public function __construct(
        protected ?string $arquivo = null,
        protected ?string $semente = null,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->todos()[$id] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function porLogin(string $login): ?array
    {
        foreach ($this->todos() as $registro) {
            if ((string) $registro['login'] === $login) {
                return $registro;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function todos(): array
    {
        if ($this->arquivo === null) {
            return $this->memoria;
        }

        return $this->comArquivo(fn (array $registros) => $registros, gravar: false);
    }

    /**
     * Insere (sem id) ou substitui (com id) um usuário. Devolve o registro gravado.
     *
     * @param  array<string, mixed>  $registro
     * @return array<string, mixed>
     */
    public function salvar(array $registro): array
    {
        $gravar = function (array &$registros) use (&$registro) {
            $registro['id'] ??= $registros ? max(array_keys($registros)) + 1 : 1;
            $registros[$registro['id']] = $registro;

            return $registros;
        };

        if ($this->arquivo === null) {
            $gravar($this->memoria);
        } else {
            $this->comArquivo($gravar, gravar: true);
        }

        return $registro;
    }

    /**
     * Lê (e opcionalmente regrava) o arquivo com trava, para requisições simultâneas.
     *
     * @param  callable(array<int, array<string, mixed>>): array<int, array<string, mixed>>  $operacao
     * @return array<int, array<string, mixed>>
     */
    protected function comArquivo(callable $operacao, bool $gravar): array
    {
        if (! is_dir($dir = dirname($this->arquivo))) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($this->arquivo, 'c+') ?: throw new RuntimeException("Não foi possível abrir {$this->arquivo}.");

        try {
            flock($handle, LOCK_EX);
            $conteudo = stream_get_contents($handle);

            if ($conteudo === '' || $conteudo === false) {
                $registros = $this->lerSemente();
                $gravar = true;
            } else {
                $registros = $this->indexar(json_decode($conteudo, true, flags: JSON_THROW_ON_ERROR));
            }

            $registros = $operacao($registros);

            if ($gravar) {
                ftruncate($handle, 0);
                rewind($handle);
                fwrite($handle, json_encode(array_values($registros), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                fflush($handle);
            }

            return $registros;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function lerSemente(): array
    {
        if ($this->semente === null || ! is_file($this->semente)) {
            return [];
        }

        $registros = json_decode((string) file_get_contents($this->semente), true, flags: JSON_THROW_ON_ERROR);

        return $this->indexar(array_map(function (array $r) {
            $r['password'] = Hash::make((string) $r['senha']);
            unset($r['senha']);

            return $r;
        }, $registros));
    }

    /**
     * @param  list<array<string, mixed>>  $lista
     * @return array<int, array<string, mixed>>
     */
    protected function indexar(array $lista): array
    {
        return array_column($lista, null, 'id');
    }
}
