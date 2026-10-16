<?php

namespace App\Services\Portal;

use App\Services\ECidade\ECidade;
use App\Services\ECidade\Exceptions\ECidadeException;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Telas da vida escolar (alunos_controller do legado), organizadas a partir
 * dos métodos do e-cidade para a matrícula selecionada:
 *
 * - MatriculaAluno            → boletim() e parecer()   (matricula, parecerMatricula)
 * - FaltasMatriculaAluno      → faltas()                (consultaFaltas)
 * - HistoricoAluno            → historico()             (historico)
 * - HorarioAulaMatriculaAluno → horarios()              (horarios)
 * - CalendarioEscolarAluno    → calendario()            (calendarioEscolar)
 * - AlunoLeitorBiblioteca     → biblioteca()            (biblioteca)
 *
 * Métodos que dependem da matrícula devolvem null quando o aluno não tem
 * matrícula; listas vazias do e-cidade viram estruturas vazias.
 *
 * @throws ECidadeException
 */
class VidaEscolar
{
    public function __construct(
        protected ECidade $ecidade,
        protected AlunoAtual $aluno,
    ) {}

    /**
     * Matrícula, grade de aproveitamento e atividades complementares/AEE.
     */
    public function boletim(): ?array
    {
        $codigo = $this->aluno->matriculaAtiva();

        if ($codigo === null) {
            return null;
        }

        $m = $this->ecidade->matricula($codigo);

        if (! $m) {
            return null;
        }

        $grade = array_values((array) ($m['grade_aproveitamento'] ?? []));

        // Cabeçalho da tabela: períodos da primeira disciplina (como no legado)
        $periodos = array_map(fn ($p) => [
            'codigo' => (int) ($p['iCodigo'] ?? 0),
            'nome' => self::texto($p['sDescricao'] ?? null),
            'abreviatura' => self::texto($p['sDescricaoAbreviada'] ?? null) ?? self::texto($p['sDescricao'] ?? null),
            'formaAvaliacao' => self::texto($p['sFormaAvaliacao'] ?? null),
        ], array_values((array) ($grade[0]['aAproveitamento'] ?? [])));

        $resultado = self::texto($m['resultado_final_etapa'] ?? null);

        return [
            'matricula' => [
                'codigo' => (int) ($m['codigo_matricula'] ?? $codigo),
                'data' => self::data($m['data_matricula'] ?? null),
                'situacao' => self::texto($m['situacao_matricula'] ?? null),
                'escola' => self::texto($m['turma_escola'] ?? null),
                'etapa' => self::texto($m['etapa_matricula'] ?? null),
                'turma' => self::texto($m['turma_matricula'] ?? null),
                'turno' => self::texto($m['turma_turno'] ?? null),
                'calendario' => self::texto($m['turma_calendario'] ?? null),
                'resultado' => $resultado,
                'emAndamento' => $resultado === null || Str::upper(Str::ascii($resultado)) === 'EM ANDAMENTO',
            ],
            'periodos' => $periodos,
            'disciplinas' => array_map(fn ($d) => $this->disciplina($d), $grade),
            'atividades' => array_map(fn ($a) => [
                'escola' => self::texto($a['escola'] ?? null),
                'turma' => self::texto($a['nome_turma'] ?? null),
                // tipo_turma 5 = AEE no e-cidade
                'tipo' => (int) ($a['tipo_turma'] ?? 0) === 5 ? 'AEE' : 'Atividade complementar',
                'turno' => self::texto($a['turno_turma'] ?? null),
                'atividades' => self::lista($a['atividades'] ?? []),
                'profissionais' => array_map(fn ($p) => [
                    'nome' => self::texto($p['sNome'] ?? null),
                    'horarios' => array_map(fn ($h) => [
                        'dia' => self::texto($h['sDia'] ?? null),
                        'inicio' => self::texto($h['sHoraInicio'] ?? null),
                        'fim' => self::texto($h['sHoraFinal'] ?? null),
                    ], array_values((array) ($p['aHorarios'] ?? []))),
                ], array_values((array) ($a['aProfissionais'] ?? []))),
            ], array_values((array) ($m['atividades_complementares'] ?? []))),
        ];
    }

    /**
     * Parecer de uma disciplina num período (parecerMatricula no legado).
     * Null quando não há parecer, amparo nem pareceres padronizados.
     */
    public function parecer(int $regencia, int $periodo): ?array
    {
        $codigo = $this->aluno->matriculaAtiva();

        if ($codigo === null) {
            return null;
        }

        foreach ((array) ($this->ecidade->matricula($codigo)['grade_aproveitamento'] ?? []) as $d) {
            if ((int) ($d['iCodigoRegencia'] ?? 0) !== $regencia) {
                continue;
            }

            foreach ((array) ($d['aAproveitamento'] ?? []) as $p) {
                if ((int) ($p['iCodigo'] ?? 0) !== $periodo) {
                    continue;
                }

                $a = $p['oAproveitamento'] ?? [];
                $parecer = [
                    'disciplina' => self::texto($d['sNome'] ?? null),
                    'periodo' => self::texto($p['sDescricao'] ?? null),
                    'nota' => self::texto($a['nAproveitamento'] ?? null),
                    'parecer' => self::texto($a['sParecer'] ?? null),
                    'amparo' => self::texto($a['sTipoAmparo'] ?? null),
                    'padronizados' => array_values(array_filter(array_map(fn ($pp) => self::texto($pp['sDescricao'] ?? null) ? [
                        'descricao' => self::texto($pp['sDescricao'] ?? null),
                        'legenda' => self::texto($pp['sLegenda'] ?? null),
                    ] : null, (array) ($a['aPareceresPadronizados'] ?? [])))),
                ];

                return $parecer['parecer'] || $parecer['amparo'] || $parecer['padronizados'] ? $parecer : null;
            }
        }

        return null;
    }

    /**
     * Faltas por data, da mais recente para a mais antiga.
     */
    public function faltas(): ?array
    {
        $codigo = $this->aluno->matriculaAtiva();

        if ($codigo === null) {
            return null;
        }

        $faltas = [];

        foreach ((array) $this->ecidade->faltas($codigo) as $f) {
            $data = self::data($f['dtFalta'] ?? null);

            if ($data === null) {
                continue;
            }

            $faltas[] = [
                'data' => $data,
                'disciplinas' => array_values(array_filter(array_map('trim', explode(',', (string) ($f['sDisciplinas'] ?? ''))))),
                'total' => (int) ($f['iTotalFaltas'] ?? 0),
            ];
        }

        usort($faltas, fn ($a, $b) => strcmp($b['data'], $a['data']));

        return [
            'faltas' => $faltas,
            'total' => array_sum(array_column($faltas, 'total')),
            'dias' => count($faltas),
        ];
    }

    /**
     * Histórico escolar por etapa, da mais recente para a mais antiga.
     */
    public function historico(): array
    {
        $etapas = array_map(fn ($e) => [
            'etapa' => self::texto($e['etapa'] ?? null),
            'ano' => isset($e['ano_etapa']) ? (int) $e['ano_etapa'] : null,
            'curso' => self::texto($e['curso'] ?? null),
            'escola' => self::texto($e['escola_etapa'] ?? null),
            'resultado' => self::texto($e['resultado_etapa'] ?? null),
            'diasLetivos' => self::texto($e['dias_letivos'] ?? null),
            'cargaHoraria' => self::texto($e['carga_horaria'] ?? null),
            'disciplinas' => array_map(fn ($d) => [
                'nome' => self::texto($d['nome_disciplina'] ?? null),
                'situacao' => self::texto($d['situacao_disciplina'] ?? null),
                'aproveitamento' => self::texto($d['aproveitamento_disciplina'] ?? null),
                'resultado' => self::texto($d['resultado_disciplina'] ?? null),
                'cargaHoraria' => self::texto($d['carga_horaria'] ?? null),
            ], array_values((array) ($e['disciplinas_etapa'] ?? []))),
        ], array_values((array) ($this->ecidade->historico($this->aluno->codigo())['etapas'] ?? [])));

        usort($etapas, fn ($a, $b) => ($b['ano'] ?? 0) <=> ($a['ano'] ?? 0));

        return ['etapas' => $etapas];
    }

    /**
     * Grade de horários por turno: períodos (linhas) × dias da semana (colunas).
     */
    public function horarios(): ?array
    {
        $codigo = $this->aluno->matriculaAtiva();

        if ($codigo === null) {
            return null;
        }

        $turnos = [];

        foreach ((array) ($this->ecidade->horarios($codigo)['turma']['turnos'] ?? []) as $turno) {
            $dias = array_values((array) ($turno['dias_semana'] ?? []));
            $periodos = [];

            // O legado usa os períodos do primeiro dia como linhas da grade
            foreach (array_values((array) ($dias[0]['periodos'] ?? [])) as $i => $p) {
                $periodos[] = [
                    'nome' => self::texto($p['nome_periodo'] ?? null),
                    'inicio' => self::texto($p['inicio_periodo'] ?? null),
                    'fim' => self::texto($p['termino_periodo'] ?? null),
                    'aulas' => array_map(function ($dia) use ($i) {
                        $aula = array_values((array) ($dia['periodos'] ?? []))[$i] ?? null;
                        $disciplina = self::texto($aula['disciplina'] ?? null);

                        return $disciplina ? ['disciplina' => $disciplina, 'professor' => self::texto($aula['professor'] ?? null)] : null;
                    }, $dias),
                ];
            }

            $turnos[] = [
                'nome' => self::texto($turno['nome'] ?? null),
                'dias' => array_map(fn ($d) => self::texto($d['nome'] ?? null), $dias),
                'periodos' => $periodos,
            ];
        }

        return ['turnos' => $turnos];
    }

    /**
     * Calendário escolar: período letivo, bimestres e dias com eventos.
     */
    public function calendario(): ?array
    {
        $codigo = $this->aluno->matriculaAtiva();

        if ($codigo === null) {
            return null;
        }

        $c = $this->ecidade->calendario($codigo);

        if (! $c) {
            return null;
        }

        return [
            'ano' => isset($c['ano']) ? (int) $c['ano'] : null,
            'inicio' => self::data($c['data_inicio'] ?? null),
            'fim' => self::data($c['data_fim'] ?? null),
            'periodos' => array_map(fn ($p) => [
                'nome' => self::texto($p['nome'] ?? null),
                'inicio' => self::data($p['data_inicio'] ?? null),
                'fim' => self::data($p['data_termino'] ?? null),
            ], array_values((array) ($c['periodos_letivos'] ?? []))),
            'meses' => array_map(fn ($m) => [
                'nome' => self::texto($m['nome'] ?? null),
                'dias' => array_values(array_filter(array_map(fn ($d) => self::data($d['data'] ?? null) ? [
                    'data' => self::data($d['data'] ?? null),
                    'letivo' => (bool) ($d['dia_letivo']['dia_letivo'] ?? false),
                    'periodo' => self::texto($d['dia_letivo']['periodo'] ?? null),
                    'eventos' => array_map(fn ($e) => [
                        'nome' => self::texto($e['nome'] ?? null),
                        'letivo' => (bool) ($e['letivo'] ?? false),
                    ], array_values((array) ($d['eventos'] ?? []))),
                ] : null, (array) ($m['dias'] ?? [])))),
            ], array_values((array) ($c['meses'] ?? []))),
        ];
    }

    /**
     * Empréstimos da biblioteca, com filtro opcional pelo período de retirada.
     */
    public function biblioteca(?DateTimeInterface $de = null, ?DateTimeInterface $ate = null, ?CarbonImmutable $hoje = null): array
    {
        $hoje = ($hoje ?? CarbonImmutable::now())->format('Y-m-d');

        $emprestimos = array_map(function ($e) use ($hoje) {
            $prevista = self::data($e['data_devolucao_prevista'] ?? null);
            $devolvido = self::data($e['data_devolucao'] ?? null);

            return [
                'titulo' => self::texto($e['nome_exemplar'] ?? null),
                // "biblicoteca": grafia do próprio e-cidade
                'biblioteca' => self::texto($e['biblicoteca'] ?? ($e['biblioteca'] ?? null)),
                'retirada' => self::data($e['data_retirada'] ?? null),
                'devolverAte' => $prevista,
                'devolvidoEm' => $devolvido,
                'situacao' => $devolvido ? 'devolvido' : ($prevista !== null && $prevista < $hoje ? 'atrasado' : 'emprestado'),
            ];
        }, array_values((array) ($this->ecidade->biblioteca($this->aluno->codigo(), $de, $ate)['emprestimos'] ?? [])));

        usort($emprestimos, fn ($a, $b) => strcmp((string) $b['retirada'], (string) $a['retirada']));

        return ['emprestimos' => $emprestimos];
    }

    protected function disciplina(array $d): array
    {
        return [
            'regencia' => (int) ($d['iCodigoRegencia'] ?? 0),
            'nome' => self::texto($d['sNome'] ?? null),
            'notas' => array_map(function ($p) {
                $a = $p['oAproveitamento'] ?? [];

                return [
                    'periodo' => (int) ($p['iCodigo'] ?? 0),
                    'nota' => self::texto($a['nAproveitamento'] ?? null),
                    'faltas' => (int) ($a['iFaltas'] ?? 0),
                    'atingiuMinimo' => ($a['lAtingiuMinimo'] ?? true) !== false,
                    'temParecer' => self::texto($a['sParecer'] ?? null) !== null
                        || self::texto($a['sTipoAmparo'] ?? null) !== null
                        || ! empty($a['aPareceresPadronizados']),
                ];
            }, array_values((array) ($d['aAproveitamento'] ?? []))),
            'aulas' => isset($d['oFrequencia']['iTotalAulas']) ? (int) $d['oFrequencia']['iTotalAulas'] : null,
            'frequencia' => self::numero($d['oFrequencia']['nPercentualFrequencia'] ?? null),
            'notaFinal' => self::texto($d['oResultadoFinal']['nAproveitamentoFinal'] ?? null),
            'resultado' => self::texto($d['oResultadoFinal']['sTermoResultadoFinal'] ?? null),
            'resultadoAbreviado' => self::texto($d['oResultadoFinal']['sTermoResultadoFinalAbreviado'] ?? null),
            'progressaoParcial' => (bool) ($d['oResultadoFinal']['lAprovadoProgressaoParcial'] ?? false),
        ];
    }

    protected static function texto(mixed $valor): ?string
    {
        return is_scalar($valor) && trim((string) $valor) !== '' ? trim((string) $valor) : null;
    }

    protected static function data(mixed $valor): ?string
    {
        $valor = self::texto($valor);

        return $valor && preg_match('/^\d{4}-\d{2}-\d{2}/', $valor) ? substr($valor, 0, 10) : null;
    }

    protected static function numero(mixed $valor): ?float
    {
        if (is_string($valor)) {
            $valor = str_replace(',', '.', trim($valor));
        }

        return is_numeric($valor) ? (float) $valor : null;
    }

    /**
     * @return list<string>
     */
    protected static function lista(mixed $valores): array
    {
        return array_values(array_filter(array_map(fn ($v) => self::texto($v), (array) $valores)));
    }
}
