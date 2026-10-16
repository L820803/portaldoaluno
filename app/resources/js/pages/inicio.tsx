import { AlunoAvatar } from '@/components/portal/aluno-avatar';
import { EmptyState } from '@/components/portal/empty-state';
import { SectionCard } from '@/components/portal/section-card';
import { StatCard } from '@/components/portal/stat-card';
import { StatusBadge, toneDaSituacao } from '@/components/portal/status-badge';
import { useUsuario } from '@/components/portal/user-menu';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import PortalLayout from '@/layouts/portal-layout';
import { formatarData, fraseCase, primeiroNome, rotuloMatricula, saudacao, titleCase } from '@/lib/format';
import { navegacao } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { ehErro, type AulasDoDia, type Emprestimo, type ErroConsulta, type ResumoMatricula } from '@/types/aluno';
import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookMarked,
    CalendarCheck,
    CalendarX2,
    ChevronRight,
    CircleAlert,
    Clock,
    CloudOff,
    GraduationCap,
    LibraryBig,
    PartyPopper,
    RefreshCw,
    School,
    TrendingUp,
} from 'lucide-react';
import * as React from 'react';
import { useState } from 'react';

interface InicioProps {
    matricula?: ResumoMatricula | ErroConsulta | null;
    aulas?: AulasDoDia | ErroConsulta | null;
    emprestimos?: { itens: Emprestimo[] } | ErroConsulta | null;
}

/** Frequência mínima exigida (LDB, art. 24) */
const FREQUENCIA_MINIMA = 75;

/**
 * Falha do e-cidade num cartão: tenta de novo só aquela informação.
 */
function ErroCartao({ prop, className }: { prop: keyof InicioProps; className?: string }) {
    const [carregando, setCarregando] = useState(false);

    return (
        <div className={cn('flex flex-col items-center justify-center gap-3 px-4 py-8 text-center', className)}>
            <CloudOff className="text-danger-ink size-6" />
            <p className="text-muted-foreground text-sm">Não foi possível carregar agora.</p>
            <Button
                variant="outline"
                size="sm"
                disabled={carregando}
                onClick={() => router.reload({ only: [prop], onStart: () => setCarregando(true), onFinish: () => setCarregando(false) })}
            >
                <RefreshCw className={cn('size-4', carregando && 'animate-spin')} />
                Tentar novamente
            </Button>
        </div>
    );
}

function VerTudo({ href, children = 'Ver tudo' }: { href: string; children?: React.ReactNode }) {
    return (
        <Link href={href} prefetch className="text-primary inline-flex items-center gap-0.5 text-sm font-medium hover:underline">
            {children}
            <ChevronRight className="size-4" />
        </Link>
    );
}

function Indicadores({ matricula }: { matricula: InicioProps['matricula'] }) {
    if (ehErro(matricula)) {
        return (
            <SectionCard>
                <ErroCartao prop="matricula" />
            </SectionCard>
        );
    }

    if (!matricula) {
        return null;
    }

    const f = matricula.frequencia;
    const tomFrequencia = f === null ? 'info' : f >= 85 ? 'success' : f >= FREQUENCIA_MINIMA ? 'warning' : 'danger';
    const abaixo = matricula.abaixoDaMedia.length;

    return (
        <div className="grid grid-cols-3 gap-2 sm:gap-4">
            <StatCard
                label="Frequência geral"
                value={f === null ? '—' : `${f.toLocaleString('pt-BR')}%`}
                hint={`Mínimo exigido: ${FREQUENCIA_MINIMA}%`}
                icon={TrendingUp}
                tone={tomFrequencia}
            />
            <StatCard
                label="Faltas no ano"
                value={matricula.faltas}
                hint={matricula.ultimoPeriodo ? `Até o ${titleCase(matricula.ultimoPeriodo)}` : undefined}
                icon={CalendarX2}
                tone={matricula.faltas > 0 ? 'warning' : 'success'}
            />
            <StatCard
                label="Notas abaixo da média"
                value={abaixo}
                hint={`Em ${matricula.disciplinas} disciplinas`}
                icon={abaixo > 0 ? CircleAlert : GraduationCap}
                tone={abaixo > 0 ? 'danger' : 'success'}
            />
        </div>
    );
}

function Atencao({ matricula }: { matricula: InicioProps['matricula'] }) {
    if (ehErro(matricula) || !matricula) {
        return null;
    }

    return (
        <SectionCard title="Notas que pedem atenção" icon={CircleAlert} action={<VerTudo href={route('boletim')}>Boletim</VerTudo>}>
            {matricula.abaixoDaMedia.length === 0 ? (
                <EmptyState icon={PartyPopper} title="Nenhuma nota abaixo da média" description="Continue assim!" className="py-6" />
            ) : (
                <ul className="divide-y">
                    {matricula.abaixoDaMedia.map((n) => (
                        <li key={`${n.disciplina}-${n.periodo}`} className="flex items-center justify-between gap-3 py-2.5">
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium">{titleCase(n.disciplina)}</p>
                                <p className="text-muted-foreground text-xs">{titleCase(n.periodo)}</p>
                            </div>
                            <StatusBadge tone="danger" className="text-sm tabular-nums">
                                {n.nota}
                            </StatusBadge>
                        </li>
                    ))}
                </ul>
            )}
        </SectionCard>
    );
}

function minutos(hora: string | null): number | null {
    const m = hora ? /^(\d{1,2}):(\d{2})/.exec(hora) : null;

    return m ? Number(m[1]) * 60 + Number(m[2]) : null;
}

function Aulas({ aulas }: { aulas: InicioProps['aulas'] }) {
    const titulo = ehErro(aulas) || !aulas || aulas.hoje ? 'Aulas de hoje' : 'Próximas aulas';
    const agora = new Date().getHours() * 60 + new Date().getMinutes();

    return (
        <SectionCard
            title={titulo}
            description={!ehErro(aulas) && aulas?.dia ? titleCase(aulas.dia) : undefined}
            icon={Clock}
            action={<VerTudo href={route('horarios')}>Semana</VerTudo>}
        >
            {ehErro(aulas) ? (
                <ErroCartao prop="aulas" />
            ) : !aulas || aulas.aulas.length === 0 ? (
                <EmptyState
                    icon={CalendarCheck}
                    title="Nenhuma aula cadastrada"
                    description="Os horários da turma ainda não foram lançados."
                    className="py-6"
                />
            ) : (
                <>
                    {aulas.hoje && (minutos(aulas.aulas[aulas.aulas.length - 1].fim) ?? 24 * 60) <= agora && (
                        <p className="text-muted-foreground mb-2 text-sm">As aulas de hoje já terminaram.</p>
                    )}
                    <ol className="space-y-1">
                        {aulas.aulas.map((a, i) => {
                            const inicio = minutos(a.inicio);
                            const fim = minutos(a.fim);
                            const emAndamento = aulas.hoje && inicio !== null && fim !== null && agora >= inicio && agora < fim;
                            const encerrada = aulas.hoje && fim !== null && agora >= fim;

                            return (
                                <li
                                    key={`${a.inicio}-${i}`}
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg px-2 py-2',
                                        emAndamento && 'bg-accent',
                                        encerrada && 'opacity-55',
                                    )}
                                >
                                    <span className="text-muted-foreground w-12 shrink-0 text-xs font-medium tabular-nums">{a.inicio}</span>
                                    <span className={cn('h-8 w-1 shrink-0 rounded-full', emAndamento ? 'bg-primary' : 'bg-border')} />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">{titleCase(a.disciplina)}</p>
                                        {a.professor && <p className="text-muted-foreground truncate text-xs">{titleCase(a.professor)}</p>}
                                    </div>
                                    {emAndamento && <StatusBadge tone="info">Agora</StatusBadge>}
                                </li>
                            );
                        })}
                    </ol>
                </>
            )}
        </SectionCard>
    );
}

function Matricula({ matricula }: { matricula: InicioProps['matricula'] }) {
    if (ehErro(matricula) || !matricula) {
        return null;
    }

    return (
        <SectionCard title="Sua matrícula" icon={BookMarked} description={`Nº ${matricula.codigo}`}>
            <dl className="space-y-3 text-sm">
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-muted-foreground">Situação</dt>
                    <dd>
                        <StatusBadge tone={toneDaSituacao(matricula.situacao)} dot>
                            {fraseCase(matricula.situacao) || '—'}
                        </StatusBadge>
                    </dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-muted-foreground">Resultado</dt>
                    <dd>
                        <StatusBadge tone={toneDaSituacao(matricula.resultado)}>{fraseCase(matricula.resultado) || '—'}</StatusBadge>
                    </dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-muted-foreground">Turma</dt>
                    <dd className="text-right font-medium">
                        {[titleCase(matricula.turma), titleCase(matricula.turno)].filter(Boolean).join(' · ') || '—'}
                    </dd>
                </div>
                {matricula.escola && (
                    <div className="border-t pt-3">
                        <dt className="sr-only">Escola</dt>
                        <dd className="flex items-start gap-2">
                            <School className="text-muted-foreground mt-0.5 size-4 shrink-0" />
                            <Link href={route('minha-escola')} className="font-medium hover:underline">
                                {titleCase(matricula.escola)}
                            </Link>
                        </dd>
                    </div>
                )}
            </dl>
        </SectionCard>
    );
}

function Livros({ emprestimos }: { emprestimos: InicioProps['emprestimos'] }) {
    return (
        <SectionCard title="Livros com você" icon={LibraryBig} action={<VerTudo href={route('biblioteca')} />}>
            {ehErro(emprestimos) ? (
                <ErroCartao prop="emprestimos" />
            ) : !emprestimos || emprestimos.itens.length === 0 ? (
                <p className="text-muted-foreground py-2 text-sm">Nenhum livro para devolver.</p>
            ) : (
                <ul className="divide-y">
                    {emprestimos.itens.map((l, i) => (
                        <li key={`${l.titulo}-${i}`} className="py-2.5">
                            <p className="text-sm font-medium">{titleCase(l.titulo)}</p>
                            <p className={cn('mt-0.5 text-xs', l.atrasado ? 'text-danger-ink font-semibold' : 'text-muted-foreground')}>
                                {l.atrasado ? 'Devolução atrasada desde ' : 'Devolver até '}
                                {formatarData(l.devolverAte, 'curto') || '—'}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </SectionCard>
    );
}

function Carregando({ className }: { className?: string }) {
    return <Skeleton className={cn('h-40 rounded-xl', className)} />;
}

export default function Inicio({ matricula, aulas, emprestimos }: InicioProps) {
    const { aluno } = usePage<SharedData>().props;
    const { nome, foto } = useUsuario();
    const ativa = aluno?.matriculas.find((m) => m.codigo === aluno.matriculaAtiva);

    return (
        <PortalLayout>
            <Head title="Início" />

            {/* Boas-vindas */}
            <section className="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-[hsl(221,83%,46%)] to-[hsl(232,70%,36%)] p-6 text-white shadow-sm md:p-8 dark:from-[hsl(221,65%,30%)] dark:to-[hsl(232,55%,22%)]">
                <div aria-hidden className="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full bg-white/10" />
                <div aria-hidden className="pointer-events-none absolute right-24 -bottom-20 size-40 rounded-full bg-white/5" />

                <div className="relative flex items-center gap-4">
                    <AlunoAvatar nome={nome} foto={foto} className="size-14 ring-4 ring-white/20 md:size-16" />
                    <div className="min-w-0">
                        <p className="text-sm text-white/80">{saudacao()},</p>
                        <h1 className="truncate text-2xl font-bold tracking-tight md:text-3xl">{primeiroNome(nome)}!</h1>
                        {ativa && <p className="mt-0.5 text-sm text-white/80">{rotuloMatricula(ativa)}</p>}
                    </div>
                </div>
            </section>

            {aluno && aluno.matriculas.length > 0 && (
                <div className="space-y-6">
                    <Deferred
                        data="matricula"
                        fallback={
                            <div className="grid grid-cols-3 gap-2 sm:gap-4">
                                <Carregando className="h-24 sm:h-32" />
                                <Carregando className="h-24 sm:h-32" />
                                <Carregando className="h-24 sm:h-32" />
                            </div>
                        }
                    >
                        <Indicadores matricula={matricula} />
                    </Deferred>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <Deferred data="aulas" fallback={<Carregando className="h-72" />}>
                            <Aulas aulas={aulas} />
                        </Deferred>
                        <Deferred data="matricula" fallback={<Carregando className="h-72" />}>
                            <Atencao matricula={matricula} />
                        </Deferred>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <Deferred data="matricula" fallback={<Carregando />}>
                            <Matricula matricula={matricula} />
                        </Deferred>
                        <Deferred data="emprestimos" fallback={<Carregando />}>
                            <Livros emprestimos={emprestimos} />
                        </Deferred>
                    </div>
                </div>
            )}

            {aluno && aluno.matriculas.length === 0 && !aluno.indisponivel && (
                <SectionCard>
                    <EmptyState icon={BookMarked} title="Nenhuma matrícula encontrada" description="Procure a secretaria da sua escola." />
                </SectionCard>
            )}

            {/* Acesso rápido */}
            <div className="mt-10 space-y-6">
                {navegacao.map((grupo) => (
                    <section key={grupo.title} aria-labelledby={`grupo-${grupo.title}`}>
                        <h2 id={`grupo-${grupo.title}`} className="text-muted-foreground mb-3 text-xs font-semibold tracking-wider uppercase">
                            {grupo.title}
                        </h2>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            {grupo.items.map((item) => (
                                <Link
                                    key={item.route}
                                    href={route(item.route)}
                                    prefetch
                                    className="group bg-card hover:border-primary/40 focus-visible:ring-ring flex items-center gap-3 rounded-xl border p-3 shadow-xs outline-hidden transition-all hover:shadow-md focus-visible:ring-2"
                                >
                                    <div className="bg-accent text-accent-foreground group-hover:bg-primary group-hover:text-primary-foreground flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors">
                                        <item.icon className="size-[18px]" />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="truncate text-sm leading-tight font-semibold">{item.short ?? item.title}</p>
                                        {item.description && <p className="text-muted-foreground mt-0.5 truncate text-xs">{item.description}</p>}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </PortalLayout>
    );
}
