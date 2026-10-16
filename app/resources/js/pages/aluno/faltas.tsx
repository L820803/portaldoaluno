import { EmptyState } from '@/components/portal/empty-state';
import { FichaPage } from '@/components/portal/ficha-page';
import { SectionCard } from '@/components/portal/section-card';
import { StatCard } from '@/components/portal/stat-card';
import { StatusBadge } from '@/components/portal/status-badge';
import { fraseCase, parseData, titleCase } from '@/lib/format';
import { type Faltas as FaltasDados, type FichaProps } from '@/types/aluno';
import { Link } from '@inertiajs/react';
import { CalendarCheck, CalendarDays, CalendarX2 } from 'lucide-react';

/**
 * Agrupa as faltas por mês ("outubro de 2026").
 */
function porMes(faltas: FaltasDados['faltas']) {
    const grupos = new Map<string, FaltasDados['faltas']>();

    for (const f of faltas) {
        const mes = parseData(f.data)?.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }) ?? '';
        grupos.set(mes, [...(grupos.get(mes) ?? []), f]);
    }

    return [...grupos.entries()];
}

export default function Faltas(props: FichaProps<FaltasDados>) {
    return (
        <FichaPage
            title="Faltas"
            description="Dias e disciplinas em que houve falta, na matrícula selecionada."
            icon={CalendarX2}
            vazio="Matrícula não encontrada"
            rodape={false}
            {...props}
        >
            {(d) => (
                <>
                    <div className="grid grid-cols-2 gap-3 sm:gap-4">
                        <StatCard label="Total de faltas" value={d.total} icon={CalendarX2} tone={d.total > 0 ? 'warning' : 'success'} />
                        <StatCard label="Dias com falta" value={d.dias} icon={CalendarDays} tone={d.dias > 0 ? 'warning' : 'success'} />
                    </div>

                    {d.faltas.length === 0 ? (
                        <SectionCard>
                            <EmptyState icon={CalendarCheck} title="Nenhuma falta registrada" description="Parabéns pela presença!" />
                        </SectionCard>
                    ) : (
                        porMes(d.faltas).map(([mes, faltas]) => (
                            <SectionCard
                                key={mes}
                                title={fraseCase(mes)}
                                description={`${faltas.length} ${faltas.length === 1 ? 'dia' : 'dias'}`}
                                flush
                            >
                                <ul className="divide-y border-t">
                                    {faltas.map((f) => {
                                        const data = parseData(f.data);

                                        return (
                                            <li key={f.data} className="flex items-center gap-4 px-5 py-3">
                                                <div className="bg-muted flex size-12 shrink-0 flex-col items-center justify-center rounded-lg leading-none">
                                                    <span className="text-lg font-bold tabular-nums">{data?.getDate()}</span>
                                                    <span className="text-muted-foreground text-[10px] uppercase">
                                                        {data?.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', '')}
                                                    </span>
                                                </div>
                                                <div className="flex min-w-0 flex-1 flex-wrap gap-1.5">
                                                    {f.disciplinas.length > 0 ? (
                                                        f.disciplinas.map((disc) => <StatusBadge key={disc}>{titleCase(disc)}</StatusBadge>)
                                                    ) : (
                                                        <span className="text-muted-foreground text-sm">Disciplina não informada</span>
                                                    )}
                                                </div>
                                                <span className="text-muted-foreground shrink-0 text-sm whitespace-nowrap">
                                                    <strong className="text-foreground tabular-nums">{f.total}</strong>{' '}
                                                    {f.total === 1 ? 'falta' : 'faltas'}
                                                </span>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </SectionCard>
                        ))
                    )}

                    <p className="text-muted-foreground text-center text-sm">
                        As faltas por disciplina e a frequência estão no{' '}
                        <Link href={route('boletim')} className="text-primary font-medium hover:underline">
                            boletim
                        </Link>
                        .
                    </p>
                </>
            )}
        </FichaPage>
    );
}
