import { FichaPage } from '@/components/portal/ficha-page';
import { InfoGrid } from '@/components/portal/info-grid';
import { ParecerDialog, type ParecerAlvo } from '@/components/portal/parecer-dialog';
import { SectionCard } from '@/components/portal/section-card';
import { StatusBadge, toneDaSituacao } from '@/components/portal/status-badge';
import { formatarData, fraseCase, titleCase } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type Boletim as BoletimDados, type FichaProps } from '@/types/aluno';
import { ChevronDown, GraduationCap, MessageSquareText, Puzzle } from 'lucide-react';
import { useState } from 'react';

type Disciplina = BoletimDados['disciplinas'][number];
type Nota = Disciplina['notas'][number];

const FREQUENCIA_MINIMA = 75;

function Frequencia({ valor }: { valor: number | null }) {
    if (valor === null) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <span className={cn('font-medium tabular-nums', valor < FREQUENCIA_MINIMA && 'text-danger-ink font-semibold')}>
            {valor.toLocaleString('pt-BR')}%
        </span>
    );
}

/**
 * Nota de um período: vermelha abaixo da média; com parecer, vira botão.
 */
function NotaCelula({ nota, onParecer }: { nota: Nota | undefined; onParecer: () => void }) {
    if (!nota?.nota) {
        return <span className="text-muted-foreground/60">—</span>;
    }

    const texto = (
        <span
            className={cn(
                'inline-flex min-w-9 items-center justify-center gap-1 rounded-md px-1.5 py-0.5 font-semibold tabular-nums',
                !nota.atingiuMinimo && 'bg-danger-soft text-danger-ink',
            )}
        >
            {nota.nota}
            {nota.temParecer && <MessageSquareText className="size-3" aria-hidden />}
        </span>
    );

    return nota.temParecer ? (
        <button
            type="button"
            onClick={onParecer}
            className="hover:bg-muted focus-visible:ring-ring rounded-md underline decoration-dotted underline-offset-4 outline-hidden focus-visible:ring-2"
            aria-label={`Nota ${nota.nota}. Ver parecer`}
        >
            {texto}
        </button>
    ) : (
        texto
    );
}

function Resultado({ d }: { d: Disciplina }) {
    if (!d.resultadoAbreviado && !d.resultado) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <StatusBadge tone={toneDaSituacao(d.resultado)} title={fraseCase(d.resultado)}>
            {d.resultadoAbreviado ?? fraseCase(d.resultado)}
            {d.progressaoParcial && '*'}
        </StatusBadge>
    );
}

export default function Boletim(props: FichaProps<BoletimDados>) {
    const [alvo, setAlvo] = useState<ParecerAlvo | null>(null);
    const [aberta, setAberta] = useState<number | null>(null);

    return (
        <FichaPage
            title="Boletim"
            description="Notas, faltas e frequência da matrícula selecionada."
            icon={GraduationCap}
            vazio="Matrícula não encontrada"
            rodape={false}
            {...props}
        >
            {(b) => {
                const parecer = (d: Disciplina, n: Nota) =>
                    setAlvo({
                        regencia: d.regencia,
                        periodo: n.periodo,
                        disciplina: d.nome,
                        nomePeriodo: b.periodos.find((p) => p.codigo === n.periodo)?.nome ?? null,
                    });
                const temParecer = b.disciplinas.some((d) => d.notas.some((n) => n.temParecer));
                const temProgressao = b.disciplinas.some((d) => d.progressaoParcial);

                return (
                    <>
                        <SectionCard>
                            <InfoGrid
                                columns={4}
                                items={[
                                    { label: 'Escola', value: titleCase(b.matricula.escola), full: true },
                                    {
                                        label: 'Turma / turno',
                                        value: [titleCase(b.matricula.turma), titleCase(b.matricula.turno)].filter(Boolean).join(' · '),
                                    },
                                    { label: 'Etapa', value: titleCase(b.matricula.etapa) },
                                    { label: 'Matrícula nº', value: String(b.matricula.codigo) },
                                    { label: 'Data da matrícula', value: formatarData(b.matricula.data) },
                                    {
                                        label: 'Situação',
                                        value: b.matricula.situacao && (
                                            <StatusBadge tone={toneDaSituacao(b.matricula.situacao)} dot>
                                                {fraseCase(b.matricula.situacao)}
                                            </StatusBadge>
                                        ),
                                    },
                                    {
                                        label: b.matricula.emAndamento ? 'Resultado parcial' : 'Resultado final',
                                        value: b.matricula.resultado && (
                                            <StatusBadge tone={toneDaSituacao(b.matricula.resultado)}>{fraseCase(b.matricula.resultado)}</StatusBadge>
                                        ),
                                    },
                                    { label: 'Calendário', value: titleCase(b.matricula.calendario) },
                                ]}
                            />
                        </SectionCard>

                        <SectionCard title="Notas e frequência" icon={GraduationCap} flush>
                            {b.disciplinas.length === 0 ? (
                                <p className="text-muted-foreground px-5 pb-5 text-sm">Nenhuma nota lançada ainda.</p>
                            ) : (
                                <>
                                    {/* Computador: tabela completa */}
                                    <div className="hidden overflow-x-auto md:block">
                                        <table className="w-full text-sm">
                                            <caption className="sr-only">Notas por disciplina e período</caption>
                                            <thead>
                                                <tr className="text-muted-foreground border-y text-xs font-semibold tracking-wide uppercase">
                                                    <th scope="col" className="px-5 py-3 text-left">
                                                        Disciplina
                                                    </th>
                                                    {b.periodos.map((p) => (
                                                        <th key={p.codigo} scope="col" className="px-2 py-3 text-center" title={titleCase(p.nome)}>
                                                            {titleCase(p.abreviatura)}
                                                        </th>
                                                    ))}
                                                    <th scope="col" className="px-2 py-3 text-center">
                                                        Faltas
                                                    </th>
                                                    <th scope="col" className="px-2 py-3 text-center">
                                                        Freq.
                                                    </th>
                                                    <th scope="col" className="px-2 py-3 text-center">
                                                        {b.matricula.emAndamento ? 'Média' : 'Final'}
                                                    </th>
                                                    {!b.matricula.emAndamento && (
                                                        <th scope="col" className="px-5 py-3 text-center">
                                                            Resultado
                                                        </th>
                                                    )}
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y">
                                                {b.disciplinas.map((d) => (
                                                    <tr key={d.regencia} className="hover:bg-muted/40">
                                                        <th scope="row" className="px-5 py-3 text-left font-medium">
                                                            {titleCase(d.nome)}
                                                        </th>
                                                        {b.periodos.map((p) => {
                                                            const n = d.notas.find((x) => x.periodo === p.codigo);

                                                            return (
                                                                <td key={p.codigo} className="px-2 py-3 text-center">
                                                                    <NotaCelula nota={n} onParecer={() => n && parecer(d, n)} />
                                                                </td>
                                                            );
                                                        })}
                                                        <td className="px-2 py-3 text-center tabular-nums">
                                                            {d.notas.reduce((s, n) => s + n.faltas, 0)}
                                                        </td>
                                                        <td className="px-2 py-3 text-center">
                                                            <Frequencia valor={d.frequencia} />
                                                        </td>
                                                        <td className="px-2 py-3 text-center font-bold tabular-nums">{d.notaFinal ?? '—'}</td>
                                                        {!b.matricula.emAndamento && (
                                                            <td className="px-5 py-3 text-center">
                                                                <Resultado d={d} />
                                                            </td>
                                                        )}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Celular: uma disciplina por linha, toque para ver os períodos */}
                                    <ul className="divide-y border-t md:hidden">
                                        {b.disciplinas.map((d) => {
                                            const expandida = aberta === d.regencia;
                                            const abaixo = d.notas.some((n) => n.nota && !n.atingiuMinimo);

                                            return (
                                                <li key={d.regencia}>
                                                    <button
                                                        type="button"
                                                        onClick={() => setAberta(expandida ? null : d.regencia)}
                                                        aria-expanded={expandida}
                                                        className="flex w-full items-center gap-3 px-4 py-3 text-left"
                                                    >
                                                        <div className="min-w-0 flex-1">
                                                            <p className="truncate font-medium">{titleCase(d.nome)}</p>
                                                            <p className="text-muted-foreground text-xs">
                                                                Frequência <Frequencia valor={d.frequencia} />
                                                                {abaixo && (
                                                                    <span className="text-danger-ink font-semibold"> · nota abaixo da média</span>
                                                                )}
                                                            </p>
                                                        </div>
                                                        <span className="text-lg font-bold tabular-nums">{d.notaFinal ?? '—'}</span>
                                                        <ChevronDown
                                                            className={cn(
                                                                'text-muted-foreground size-4 transition-transform',
                                                                expandida && 'rotate-180',
                                                            )}
                                                        />
                                                    </button>
                                                    {expandida && (
                                                        <div className="grid grid-cols-2 gap-2 px-4 pb-4">
                                                            {b.periodos.map((p) => {
                                                                const n = d.notas.find((x) => x.periodo === p.codigo);

                                                                return (
                                                                    <div
                                                                        key={p.codigo}
                                                                        className="bg-muted/50 flex items-center justify-between rounded-lg px-3 py-2"
                                                                    >
                                                                        <div>
                                                                            <p className="text-muted-foreground text-xs">
                                                                                {titleCase(p.abreviatura)}
                                                                            </p>
                                                                            {n && n.faltas > 0 && (
                                                                                <p className="text-muted-foreground text-[11px]">
                                                                                    {n.faltas} {n.faltas === 1 ? 'falta' : 'faltas'}
                                                                                </p>
                                                                            )}
                                                                        </div>
                                                                        <NotaCelula nota={n} onParecer={() => n && parecer(d, n)} />
                                                                    </div>
                                                                );
                                                            })}
                                                            {!b.matricula.emAndamento && (
                                                                <div className="col-span-2 flex items-center justify-between pt-1 text-sm">
                                                                    <span className="text-muted-foreground">Resultado</span>
                                                                    <Resultado d={d} />
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                </li>
                                            );
                                        })}
                                    </ul>

                                    <div className="text-muted-foreground flex flex-wrap gap-x-5 gap-y-1 border-t px-5 py-3 text-xs">
                                        <span className="inline-flex items-center gap-1.5">
                                            <span className="bg-danger-soft size-3 rounded-sm" /> Abaixo da média
                                        </span>
                                        {temParecer && (
                                            <span className="inline-flex items-center gap-1.5">
                                                <MessageSquareText className="size-3" /> Toque na nota para ver o parecer
                                            </span>
                                        )}
                                        <span>Frequência mínima: {FREQUENCIA_MINIMA}%</span>
                                        {temProgressao && <span>* Aprovado com progressão parcial</span>}
                                    </div>
                                </>
                            )}
                        </SectionCard>

                        {b.atividades.length > 0 && (
                            <SectionCard title="Atividades complementares / AEE" icon={Puzzle}>
                                <div className="grid gap-4 lg:grid-cols-2">
                                    {b.atividades.map((a, i) => (
                                        <div key={`${a.turma}-${i}`} className="rounded-lg border p-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <p className="font-semibold">{titleCase(a.turma)}</p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {[titleCase(a.escola), a.turno && `Turno ${titleCase(a.turno).toLowerCase()}`]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </p>
                                                </div>
                                                <StatusBadge tone="info">{a.tipo}</StatusBadge>
                                            </div>
                                            {a.atividades.length > 0 && (
                                                <div className="mt-3 flex flex-wrap gap-1.5">
                                                    {a.atividades.map((at) => (
                                                        <StatusBadge key={at}>{titleCase(at)}</StatusBadge>
                                                    ))}
                                                </div>
                                            )}
                                            {a.profissionais.map((p, j) => (
                                                <div key={`${p.nome}-${j}`} className="mt-3 border-t pt-3 text-sm">
                                                    <p className="font-medium">{titleCase(p.nome)}</p>
                                                    <ul className="text-muted-foreground mt-1 space-y-0.5 text-xs">
                                                        {p.horarios.map((h, k) => (
                                                            <li key={k}>
                                                                {titleCase(h.dia)}: {h.inicio} às {h.fim}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            ))}
                                        </div>
                                    ))}
                                </div>
                            </SectionCard>
                        )}

                        <ParecerDialog alvo={alvo} onClose={() => setAlvo(null)} />
                    </>
                );
            }}
        </FichaPage>
    );
}
