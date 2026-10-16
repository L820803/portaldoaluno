import { EmptyState } from '@/components/portal/empty-state';
import { FichaPage } from '@/components/portal/ficha-page';
import { SectionCard } from '@/components/portal/section-card';
import { titleCase } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type FichaProps, type Horarios as HorariosDados } from '@/types/aluno';
import { CalendarCheck, Clock } from 'lucide-react';
import { useState } from 'react';

type Turno = HorariosDados['turnos'][number];

const PREFIXOS = ['DOMINGO', 'SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA', 'SABADO'];

const normalizar = (s: string | null) =>
    (s ?? '')
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toUpperCase();

/** Índice da coluna de hoje, ou -1 */
function colunaDeHoje(dias: Turno['dias']): number {
    const hoje = PREFIXOS[new Date().getDay()];

    return dias.findIndex((d) => normalizar(d).startsWith(hoje));
}

const abreviar = (dia: string | null) => titleCase(dia).slice(0, 3);

function TurnoGrade({ turno }: { turno: Turno }) {
    const hoje = colunaDeHoje(turno.dias);
    const [dia, setDia] = useState(hoje >= 0 ? hoje : 0);

    if (turno.periodos.length === 0) {
        return <p className="text-muted-foreground px-5 pb-5 text-sm">Nenhum horário cadastrado para este turno.</p>;
    }

    return (
        <>
            {/* Computador: grade semanal */}
            <div className="hidden overflow-x-auto md:block">
                <table className="w-full table-fixed text-sm">
                    <caption className="sr-only">Horários do turno {titleCase(turno.nome)}</caption>
                    <thead>
                        <tr className="border-y">
                            <th scope="col" className="text-muted-foreground w-28 px-4 py-3 text-left text-xs font-semibold tracking-wide uppercase">
                                Horário
                            </th>
                            {turno.dias.map((d, i) => (
                                <th
                                    key={`${d}-${i}`}
                                    scope="col"
                                    className={cn(
                                        'px-2 py-3 text-left text-xs font-semibold tracking-wide uppercase',
                                        i === hoje ? 'text-primary' : 'text-muted-foreground',
                                    )}
                                >
                                    {titleCase(d)}
                                    {i === hoje && (
                                        <span className="bg-primary text-primary-foreground ml-1.5 rounded px-1 py-0.5 text-[10px] normal-case">
                                            hoje
                                        </span>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {turno.periodos.map((p, i) => (
                            <tr key={`${p.inicio}-${i}`}>
                                <th scope="row" className="px-4 py-2 text-left align-top font-normal">
                                    <span className="block font-semibold tabular-nums">{p.inicio}</span>
                                    <span className="text-muted-foreground text-xs tabular-nums">até {p.fim}</span>
                                </th>
                                {p.aulas.map((a, j) => (
                                    <td key={j} className={cn('px-1.5 py-1.5 align-top', j === hoje && 'bg-accent/40')}>
                                        {a ? (
                                            <div className="bg-card h-full rounded-lg border px-2.5 py-2">
                                                <p className="text-sm leading-tight font-medium">{titleCase(a.disciplina)}</p>
                                                {a.professor && (
                                                    <p className="text-muted-foreground mt-0.5 truncate text-xs">{titleCase(a.professor)}</p>
                                                )}
                                            </div>
                                        ) : (
                                            <span className="text-muted-foreground/50 block px-2.5 py-2">—</span>
                                        )}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Celular: um dia por vez */}
            <div className="md:hidden">
                <div className="flex gap-1.5 overflow-x-auto border-t px-4 py-3" role="tablist" aria-label="Dia da semana">
                    {turno.dias.map((d, i) => (
                        <button
                            key={`${d}-${i}`}
                            type="button"
                            role="tab"
                            aria-selected={i === dia}
                            onClick={() => setDia(i)}
                            className={cn(
                                'flex min-w-14 flex-col items-center rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                i === dia ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                            )}
                        >
                            {abreviar(d)}
                            {i === hoje && <span className={cn('text-[10px]', i === dia ? 'text-primary-foreground/80' : 'text-primary')}>hoje</span>}
                        </button>
                    ))}
                </div>
                <ol className="divide-y border-t" role="tabpanel" aria-label={titleCase(turno.dias[dia])}>
                    {turno.periodos.map((p, i) => {
                        const a = p.aulas[dia];

                        return (
                            <li key={`${p.inicio}-${i}`} className="flex items-center gap-3 px-4 py-3">
                                <div className="w-14 shrink-0 text-xs tabular-nums">
                                    <span className="block font-semibold">{p.inicio}</span>
                                    <span className="text-muted-foreground">{p.fim}</span>
                                </div>
                                {a ? (
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">{titleCase(a.disciplina)}</p>
                                        {a.professor && <p className="text-muted-foreground truncate text-xs">{titleCase(a.professor)}</p>}
                                    </div>
                                ) : (
                                    <span className="text-muted-foreground text-sm">Sem aula</span>
                                )}
                            </li>
                        );
                    })}
                </ol>
            </div>
        </>
    );
}

export default function Horarios(props: FichaProps<HorariosDados>) {
    return (
        <FichaPage
            title="Horários de aula"
            description="Grade semanal da turma na matrícula selecionada."
            icon={Clock}
            vazio="Matrícula não encontrada"
            rodape={false}
            {...props}
        >
            {(h) =>
                h.turnos.length === 0 ? (
                    <SectionCard>
                        <EmptyState
                            icon={CalendarCheck}
                            title="Não há horários cadastrados para a matrícula selecionada"
                            description="Os horários aparecem aqui quando a escola monta a grade da turma."
                        />
                    </SectionCard>
                ) : (
                    h.turnos.map((t, i) => (
                        <SectionCard key={`${t.nome}-${i}`} title={`Turno ${titleCase(t.nome).toLowerCase()}`} icon={Clock} flush>
                            <TurnoGrade turno={t} />
                        </SectionCard>
                    ))
                )
            }
        </FichaPage>
    );
}
