import { EmptyState } from '@/components/portal/empty-state';
import { FichaPage } from '@/components/portal/ficha-page';
import { ResponsiveTable } from '@/components/portal/responsive-table';
import { StatusBadge, toneDaSituacao } from '@/components/portal/status-badge';
import { fraseCase, ouTraco, titleCase } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type FichaProps, type Historico as HistoricoDados } from '@/types/aluno';
import { ChevronDown, ScrollText } from 'lucide-react';
import { useState } from 'react';

type Etapa = HistoricoDados['etapas'][number];

function EtapaCartao({ etapa, abertaInicial }: { etapa: Etapa; abertaInicial: boolean }) {
    const [aberta, setAberta] = useState(abertaInicial);

    return (
        <li className="relative pl-8">
            {/* Linha do tempo */}
            <span aria-hidden className="bg-border absolute top-0 bottom-0 left-[11px] w-px" />
            <span
                aria-hidden
                className={cn(
                    'border-background absolute top-5 left-0 size-6 rounded-full border-4',
                    aberta ? 'bg-primary' : 'bg-muted-foreground/40',
                )}
            />

            <section className="bg-card rounded-xl border shadow-xs">
                <button
                    type="button"
                    onClick={() => setAberta(!aberta)}
                    aria-expanded={aberta}
                    className="flex w-full items-start gap-3 p-5 text-left"
                >
                    <div className="min-w-0 flex-1">
                        <p className="text-muted-foreground text-sm font-semibold tabular-nums">{etapa.ano ?? '—'}</p>
                        <h2 className="text-lg leading-tight font-bold">{titleCase(etapa.etapa)}</h2>
                        <p className="text-muted-foreground mt-1 text-sm">{titleCase(etapa.escola)}</p>
                    </div>
                    {etapa.resultado && (
                        <StatusBadge tone={toneDaSituacao(etapa.resultado)} dot>
                            {fraseCase(etapa.resultado)}
                        </StatusBadge>
                    )}
                    <ChevronDown className={cn('text-muted-foreground mt-1 size-5 shrink-0 transition-transform', aberta && 'rotate-180')} />
                </button>

                {aberta && (
                    <div className="border-t">
                        <dl className="text-muted-foreground flex flex-wrap gap-x-6 gap-y-1 px-5 py-3 text-sm">
                            {etapa.curso && (
                                <div>
                                    <dt className="inline">Curso: </dt>
                                    <dd className="text-foreground inline font-medium">{titleCase(etapa.curso)}</dd>
                                </div>
                            )}
                            <div>
                                <dt className="inline">Dias letivos: </dt>
                                <dd className="text-foreground inline font-medium">{ouTraco(etapa.diasLetivos)}</dd>
                            </div>
                            <div>
                                <dt className="inline">Carga horária: </dt>
                                <dd className="text-foreground inline font-medium">{etapa.cargaHoraria ? `${etapa.cargaHoraria} h` : '—'}</dd>
                            </div>
                        </dl>

                        <ResponsiveTable
                            className="border-t"
                            caption={`Disciplinas de ${titleCase(etapa.etapa)}`}
                            rows={etapa.disciplinas}
                            rowKey={(d, i) => `${d.nome}-${i}`}
                            columns={[
                                { key: 'nome', header: 'Disciplina', primary: true, cell: (d) => titleCase(d.nome) },
                                {
                                    key: 'aprov',
                                    header: 'Aproveitamento',
                                    align: 'center',
                                    cell: (d) => <strong className="tabular-nums">{ouTraco(d.aproveitamento)}</strong>,
                                },
                                { key: 'ch', header: 'Carga horária', align: 'center', cell: (d) => (d.cargaHoraria ? `${d.cargaHoraria} h` : '—') },
                                { key: 'sit', header: 'Situação', align: 'center', cell: (d) => fraseCase(d.situacao) || '—' },
                                {
                                    key: 'res',
                                    header: 'Resultado',
                                    align: 'center',
                                    cell: (d) =>
                                        d.resultado ? <StatusBadge tone={toneDaSituacao(d.resultado)}>{fraseCase(d.resultado)}</StatusBadge> : '—',
                                },
                            ]}
                        />
                    </div>
                )}
            </section>
        </li>
    );
}

export default function Historico(props: FichaProps<HistoricoDados>) {
    return (
        <FichaPage title="Histórico escolar" description="Etapas cursadas, resultados e carga horária." icon={ScrollText} {...props}>
            {(h) =>
                h.etapas.length === 0 ? (
                    <EmptyState
                        icon={ScrollText}
                        title="Nenhuma etapa no histórico"
                        description="O histórico aparece aqui quando a escola registra as etapas concluídas."
                    />
                ) : (
                    <ol className="space-y-4">
                        {h.etapas.map((e, i) => (
                            <EtapaCartao key={`${e.ano}-${e.etapa}`} etapa={e} abertaInicial={i === 0} />
                        ))}
                    </ol>
                )
            }
        </FichaPage>
    );
}
