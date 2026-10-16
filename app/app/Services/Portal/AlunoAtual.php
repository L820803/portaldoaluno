<?php

namespace App\Services\Portal;

use App\Models\User;
use App\Services\ECidade\ECidade;
use App\Services\ECidade\Exceptions\ECidadeException;
use Illuminate\Contracts\Session\Session;

/**
 * Aluno do usuário logado e a matrícula selecionada (Auth.User.Dados e
 * Auth.User.Matricula no legado).
 *
 * O login do aluno é o código do aluno no e-cidade; usuários com login não
 * numérico (admin/educação) não são alunos.
 */
class AlunoAtual
{
    public const SESSAO_MATRICULA = 'aluno.matricula';

    /** @var array<string, mixed>|null */
    protected ?array $dados = null;

    public function __construct(
        protected ECidade $ecidade,
        protected Session $session,
        protected ?User $user,
    ) {}

    public function codigo(): ?string
    {
        $login = (string) $this->user?->login;

        return ctype_digit($login) ? $login : null;
    }

    public function ehAluno(): bool
    {
        return $this->codigo() !== null;
    }

    /**
     * Resposta de AlunoDados.
     *
     * @return array<string, mixed>
     *
     * @throws ECidadeException
     */
    public function dados(): array
    {
        if (! $this->ehAluno()) {
            return [];
        }

        return $this->dados ??= $this->ecidade->alunoDados($this->codigo());
    }

    /**
     * Matrículas do aluno em ordem cronológica.
     *
     * @return list<array{codigo: int, ano: int|null, etapa: string}>
     */
    public function matriculas(): array
    {
        $matriculas = array_map(fn (array $m) => [
            'codigo' => (int) ($m['codigo_matricula'] ?? 0),
            'ano' => isset($m['ano_matricula']) ? (int) $m['ano_matricula'] : null,
            'etapa' => trim((string) ($m['etapa_matricula'] ?? '')),
        ], array_values($this->dados()['matriculas'] ?? []));

        $matriculas = array_values(array_filter($matriculas, fn (array $m) => $m['codigo'] > 0));

        usort($matriculas, fn (array $a, array $b) => [$a['ano'], $a['codigo']] <=> [$b['ano'], $b['codigo']]);

        return $matriculas;
    }

    /**
     * Código da matrícula selecionada; por padrão a mais recente, como no legado.
     */
    public function matriculaAtiva(): ?int
    {
        $codigos = array_column($this->matriculas(), 'codigo');
        $selecionada = (int) $this->session->get(self::SESSAO_MATRICULA);

        if (in_array($selecionada, $codigos, true)) {
            return $selecionada;
        }

        return $codigos ? end($codigos) : null;
    }

    /**
     * Seleciona uma matrícula do próprio aluno.
     */
    public function selecionarMatricula(int $codigo): bool
    {
        if (! in_array($codigo, array_column($this->matriculas(), 'codigo'), true)) {
            return false;
        }

        $this->session->put(self::SESSAO_MATRICULA, $codigo);

        return true;
    }

    /**
     * Foto do aluno (binário), se o e-cidade tiver.
     */
    public function foto(): ?string
    {
        $base64 = (string) ($this->dados()['foto_aluno'] ?? '');
        $binario = $base64 !== '' ? base64_decode($base64, true) : false;

        return $binario ?: null;
    }

    /**
     * URL da foto com a versão do conteúdo: o navegador guarda a imagem por
     * uma hora, e uma foto nova precisa de outra URL para aparecer na hora.
     */
    public function urlFoto(): ?string
    {
        $foto = $this->foto();

        return $foto !== null ? route('aluno.foto', ['v' => substr(md5($foto), 0, 8)]) : null;
    }

    /**
     * Dados compartilhados com todas as páginas (cabeçalho e seletor de matrícula).
     * Nunca lança exceção: sem e-cidade, devolve indisponivel = true.
     *
     * @return array<string, mixed>|null
     */
    public function resumo(): ?array
    {
        if (! $this->ehAluno()) {
            return null;
        }

        try {
            $dados = $this->dados();
        } catch (ECidadeException) {
            return [
                'codigo' => $this->codigo(),
                'nome' => $this->user->name,
                'foto' => null,
                'matriculas' => [],
                'matriculaAtiva' => null,
                'indisponivel' => true,
            ];
        }

        return [
            'codigo' => $this->codigo(),
            'nome' => trim((string) ($dados['nome_aluno'] ?? '')) ?: $this->user->name,
            'foto' => $this->urlFoto(),
            'matriculas' => $this->matriculas(),
            'matriculaAtiva' => $this->matriculaAtiva(),
            'indisponivel' => false,
        ];
    }
}
