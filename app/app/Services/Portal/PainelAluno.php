<?php

namespace App\Services\Portal;

use App\Services\ECidade\ECidade;
use App\Services\ECidade\Exceptions\ECidadeException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Resumos da página inicial, calculados a partir das respostas do e-cidade
 * para a matrícula selecionada.
 *
 * @throws ECidadeException
 */
class PainelAluno
{
    protected const DIAS = [1 => 'SEGUNDA', 2 => 'TERCA', 3 => 'QUARTA', 4 => 'QUINTA', 5 => 'SEXTA', 6 => 'SABADO', 7 => 'DOMINGO'];

    public function __construct(
        protected ECidade $ecidade,
        protected AlunoAtual $aluno,
    ) {}

    /**
     * Situação da matrícula, frequência geral e notas abaixo da média.
     */
    public function matricula(): ?array
    {
        $codigo = $this->aluno->matriculaAtiva();

        if ($codigo === null) {
            return null;
        }

        $m = $this->ecidade->matricula($codigo);
        $grade = array_values((array) ($m['grade_aproveitamento'] ?? []));

        $totalAulas = 0;
        $aulasPresentes = 0.0;
        $faltas = 0;
        $abaixo = [];
        $ultimoPeriodo = null;

        foreach ($grade as $disciplina) {
            $aulas = (int) ($disciplina['oFrequencia']['iTotalAulas'] ?? 0);
            $percentual = self::numero($disciplina['oFrequencia']['nPercentualFrequencia'] ?? null);

            if ($aulas > 0 && $percentual !== null) {
                $totalAulas += $aulas;
                $aulasPresentes += $aulas * $percentual / 100;
            }

            foreach ((array) ($disciplina['aAproveitamento'] ?? []) as $periodo) {
                $avaliacao = $periodo['oAproveitamento'] ?? [];
                $nota = trim((string) ($avaliacao['nAproveitamento'] ?? ''));
                $faltas += (int) ($avaliacao['iFaltas'] ?? 0);

                if ($nota === '') {
                    continue;
                }

                $ultimoPeriodo = $periodo['sDescricao'] ?? $ultimoPeriodo;

                if (($avaliacao['lAtingiuMinimo'] ?? true) === false) {
                    $abaixo[] = [
                        'disciplina' => $disciplina['sNome'] ?? '',
                        'periodo' => $periodo['sDescricaoAbreviada'] ?? ($periodo['sDescricao'] ?? ''),
                        'nota' => $nota,
                    ];
                }
            }
        }

        return [
            'codigo' => $codigo,
            'situacao' => self::texto($m['situacao_matricula'] ?? null),
            'resultado' => self::texto($m['resultado_final_etapa'] ?? null),
            'etapa' => self::texto($m['etapa_matricula'] ?? null),
            'turma' => self::texto($m['turma_matricula'] ?? null),
            'turno' => self::texto($m['turma_turno'] ?? null),
            'escola' => self::texto($m['turma_escola'] ?? null),
            'disciplinas' => count($grade),
            // Média ponderada pelo número de aulas de cada disciplina
            'frequencia' => $totalAulas > 0 ? round($aulasPresentes / $totalAulas * 100, 1) : null,
            'faltas' => $faltas,
            'ultimoPeriodo' => $ultimoPeriodo,
            'abaixoDaMedia' => $abaixo,
        ];
    }

    /**
     * Aulas de hoje; sem aula hoje, as do próximo dia com aula.
     */
    public function aulas(?CarbonImmutable $agora = null): ?array
    {
        $codigo = $this->aluno->matriculaAtiva();

        if ($codigo === null) {
            return null;
        }

        $agora ??= CarbonImmutable::now();
        $porDia = [];

        foreach ((array) ($this->ecidade->horarios($codigo)['turma']['turnos'] ?? []) as $turno) {
            foreach ((array) ($turno['dias_semana'] ?? []) as $dia) {
                $numero = self::numeroDoDia((string) ($dia['nome'] ?? ''));

                foreach ((array) ($dia['periodos'] ?? []) as $p) {
                    $disciplina = self::texto($p['disciplina'] ?? null);

                    if ($numero === null || $disciplina === null) {
                        continue;
                    }

                    $porDia[$numero]['nome'] = (string) $dia['nome'];
                    $porDia[$numero]['aulas'][] = [
                        'inicio' => self::texto($p['inicio_periodo'] ?? null),
                        'fim' => self::texto($p['termino_periodo'] ?? null),
                        'disciplina' => $disciplina,
                        'professor' => self::texto($p['professor'] ?? null),
                    ];
                }
            }
        }

        if (! $porDia) {
            return ['dia' => null, 'hoje' => false, 'aulas' => []];
        }

        // Procura a partir de hoje, dando a volta na semana
        for ($i = 0; $i < 7; $i++) {
            $numero = ($agora->dayOfWeekIso + $i - 1) % 7 + 1;

            if (isset($porDia[$numero])) {
                $aulas = $porDia[$numero]['aulas'];
                usort($aulas, fn ($a, $b) => strcmp((string) $a['inicio'], (string) $b['inicio']));

                return ['dia' => $porDia[$numero]['nome'], 'hoje' => $i === 0, 'aulas' => $aulas];
            }
        }

        return null;
    }

    /**
     * Livros ainda não devolvidos.
     */
    public function emprestimos(?CarbonImmutable $hoje = null): array
    {
        $hoje = ($hoje ?? CarbonImmutable::now())->format('Y-m-d');
        $pendentes = [];

        foreach ((array) ($this->ecidade->biblioteca($this->aluno->codigo())['emprestimos'] ?? []) as $e) {
            if (self::texto($e['data_devolucao'] ?? null) !== null) {
                continue;
            }

            $prevista = self::texto($e['data_devolucao_prevista'] ?? null);

            $pendentes[] = [
                'titulo' => self::texto($e['nome_exemplar'] ?? null),
                // "biblicoteca": grafia do próprio e-cidade
                'biblioteca' => self::texto($e['biblicoteca'] ?? ($e['biblioteca'] ?? null)),
                'retirada' => self::texto($e['data_retirada'] ?? null),
                'devolverAte' => $prevista,
                'atrasado' => $prevista !== null && $prevista < $hoje,
            ];
        }

        usort($pendentes, fn ($a, $b) => strcmp((string) $a['devolverAte'], (string) $b['devolverAte']));

        return $pendentes;
    }

    protected static function numeroDoDia(string $nome): ?int
    {
        $normalizado = Str::upper(Str::ascii($nome));

        foreach (self::DIAS as $numero => $prefixo) {
            if (str_starts_with($normalizado, $prefixo)) {
                return $numero;
            }
        }

        return null;
    }

    protected static function numero(mixed $valor): ?float
    {
        if (is_string($valor)) {
            $valor = str_replace(',', '.', trim($valor));
        }

        return is_numeric($valor) ? (float) $valor : null;
    }

    protected static function texto(mixed $valor): ?string
    {
        return is_scalar($valor) && trim((string) $valor) !== '' ? trim((string) $valor) : null;
    }
}
