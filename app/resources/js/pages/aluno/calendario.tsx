import { EmptyState } from '@/components/portal/empty-state';
import { FichaPage } from '@/components/portal/ficha-page';
import { SectionCard } from '@/components/portal/section-card';
import { formatarData, fraseCase, parseData, titleCase } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type Calendario as CalendarioDados, type FichaProps } from '@/types/aluno';
import { CalendarDays, CalendarHeart, PartyPopper } from 'lucide-react';

type Dia = CalendarioDados['meses'][number]['dias'][number];

/** Cores dos períodos letivos (bimestres/trimestres), na ordem em que aparecem */
const CORES = [
    'bg-sky-100 text-sky-900 dark:bg-sky-500/20 dark:text-sky-100',
    'bg-emerald-100 text-emerald-900 dark:bg-emerald-500/20 dark:text-emerald-100',
    'bg-violet-100 text-violet-900 dark:bg-violet-500/20 dark:text-violet-100',
    'bg-orange-100 text-orange-900 dark:bg-orange-500/20 dark:text-orange-100',
    'bg-teal-100 text-teal-900 dark:bg-teal-500/20 dark:text-teal-100',
    'bg-pink-100 text-pink-900 dark:bg-pink-500/20 dark:text-pink-100',
];
const COR_FERIADO = 'bg-rose-500 text-white dark:bg-rose-500/80';
const SEMANA = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];

const hojeIso = () => {
    const d = new Date();

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

/** Dia sem aula por causa de um evento (feriado, recesso...) */
const ehFeriado = (d: Dia) => d.eventos.length > 0 && !d.letivo && d.eventos.every((e) => !e.letivo);

function Mes({ mes, corDoPeriodo, hoje }: { mes: CalendarioDados['meses'][number]; corDoPeriodo: (p: string | null) => string; hoje: string }) {
    const primeiro = parseData(mes.dias[0]?.data);
    const vazios = primeiro ? primeiro.getDay() : 0;
    const eventos = mes.dias.filter((d) => d.eventos.length > 0);

    return (
        <section className="bg-card rounded-xl border p-4 shadow-xs">
            <h2 className="mb-3 font-semibold">{fraseCase(mes.nome)}</h2>

            <div className="grid grid-cols-7 gap-1 text-center text-xs" role="grid" aria-label={titleCase(mes.nome)}>
                {SEMANA.map((s, i) => (
                    <span key={i} className={cn('text-muted-foreground py-1 font-semibold', (i === 0 || i === 6) && 'opacity-60')} aria-hidden>
                        {s}
                    </span>
                ))}
                {Array.from({ length: vazios }).map((_, i) => (
                    <span key={`v${i}`} />
                ))}
                {mes.dias.map((d) => {
                    const feriado = ehFeriado(d);
                    const temEvento = d.eventos.length > 0;
                    const nomes = d.eventos.map((e) => titleCase(e.nome)).join(', ');

                    return (
                        <span
                            key={d.data}
                            title={nomes || undefined}
                            aria-label={`${formatarData(d.data, 'longo')}${d.letivo ? ', dia letivo' : ''}${nomes ? `, ${nomes}` : ''}`}
                            className={cn(
                                'relative flex aspect-square items-center justify-center rounded-md tabular-nums',
                                feriado ? COR_FERIADO : d.letivo ? corDoPeriodo(d.periodo) : 'text-muted-foreground/70',
                                d.data === hoje && 'ring-primary font-bold ring-2 ring-offset-1 ring-offset-[var(--card)]',
                            )}
                        >
                            {Number(d.data.slice(8, 10))}
                            {temEvento && !feriado && <span className="bg-primary absolute bottom-0.5 size-1 rounded-full" aria-hidden />}
                        </span>
                    );
                })}
            </div>

            {eventos.length > 0 && (
                <ul className="mt-3 space-y-1 border-t pt-3 text-xs">
                    {eventos.map((d) => (
                        <li key={d.data} className="flex gap-2">
                            <span
                                className={cn(
                                    'w-6 shrink-0 font-semibold tabular-nums',
                                    ehFeriado(d) ? 'text-rose-600 dark:text-rose-400' : 'text-primary',
                                )}
                            >
                                {d.data.slice(8, 10)}
                            </span>
                            <span className="text-muted-foreground">{d.eventos.map((e) => titleCase(e.nome)).join(', ')}</span>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export default function Calendario(props: FichaProps<CalendarioDados>) {
    return (
        <FichaPage
            title="Calendário escolar"
            description="Período letivo, bimestres, feriados e eventos."
            icon={CalendarDays}
            vazio="Não há calendário cadastrado para a matrícula selecionada"
            rodape={false}
            {...props}
        >
            {(c) => {
                const hoje = hojeIso();
                const corDoPeriodo = (p: string | null) => {
                    const i = c.periodos.findIndex((x) => x.nome === p);

                    return i >= 0 ? CORES[i % CORES.length] : 'bg-muted';
                };
                const proximos = c.meses
                    .flatMap((m) => m.dias)
                    .filter((d) => d.data >= hoje && d.eventos.length > 0)
                    .slice(0, 4);

                if (c.meses.length === 0) {
                    return (
                        <SectionCard>
                            <EmptyState icon={CalendarDays} title="Calendário sem datas cadastradas" />
                        </SectionCard>
                    );
                }

                return (
                    <>
                        <div className="grid gap-6 lg:grid-cols-[1fr_1fr]">
                            <SectionCard
                                title={`Ano letivo ${c.ano ?? ''}`}
                                description={c.inicio && c.fim ? `${formatarData(c.inicio)} a ${formatarData(c.fim)}` : undefined}
                                icon={CalendarDays}
                            >
                                <ul className="space-y-2 text-sm">
                                    {c.periodos.map((p, i) => (
                                        <li key={`${p.nome}-${i}`} className="flex items-center gap-3">
                                            <span className={cn('size-4 shrink-0 rounded', CORES[i % CORES.length])} />
                                            <span className="font-medium">{titleCase(p.nome)}</span>
                                            <span className="text-muted-foreground ml-auto text-xs tabular-nums">
                                                {formatarData(p.inicio)} – {formatarData(p.fim)}
                                            </span>
                                        </li>
                                    ))}
                                    <li className="flex items-center gap-3 border-t pt-2">
                                        <span className={cn('size-4 shrink-0 rounded', COR_FERIADO)} />
                                        <span className="font-medium">Feriado / sem aula</span>
                                    </li>
                                    <li className="flex items-center gap-3">
                                        <span className="flex size-4 shrink-0 items-end justify-center">
                                            <span className="bg-primary mb-0.5 size-1 rounded-full" />
                                        </span>
                                        <span className="font-medium">Evento em dia letivo</span>
                                    </li>
                                </ul>
                            </SectionCard>

                            <SectionCard title="Próximas datas" icon={CalendarHeart}>
                                {proximos.length === 0 ? (
                                    <EmptyState icon={PartyPopper} title="Nenhum evento pela frente" className="py-6" />
                                ) : (
                                    <ul className="divide-y">
                                        {proximos.map((d) => {
                                            const data = parseData(d.data);

                                            return (
                                                <li key={d.data} className="flex items-center gap-3 py-2.5">
                                                    <div
                                                        className={cn(
                                                            'flex size-11 shrink-0 flex-col items-center justify-center rounded-lg leading-none',
                                                            ehFeriado(d) ? COR_FERIADO : 'bg-accent text-accent-foreground',
                                                        )}
                                                    >
                                                        <span className="font-bold tabular-nums">{data?.getDate()}</span>
                                                        <span className="text-[10px] uppercase opacity-80">
                                                            {data?.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '')}
                                                        </span>
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-medium">{d.eventos.map((e) => titleCase(e.nome)).join(', ')}</p>
                                                        <p className="text-muted-foreground text-xs">
                                                            {data?.toLocaleDateString('pt-BR', { weekday: 'long' })}
                                                            {ehFeriado(d) ? ' · sem aula' : ''}
                                                        </p>
                                                    </div>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                )}
                            </SectionCard>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {c.meses.map((m, i) => (
                                <Mes key={`${m.nome}-${i}`} mes={m} corDoPeriodo={corDoPeriodo} hoje={hoje} />
                            ))}
                        </div>
                    </>
                );
            }}
        </FichaPage>
    );
}
