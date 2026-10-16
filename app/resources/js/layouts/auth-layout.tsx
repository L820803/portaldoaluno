import { Notice } from '@/components/portal/notice';
import { PortalLogo } from '@/components/portal/portal-brand';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, GraduationCap, ScrollText } from 'lucide-react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title: string;
    description?: React.ReactNode;
}

const destaques = [
    { icone: GraduationCap, texto: 'Notas, frequência e pareceres' },
    { icone: CalendarDays, texto: 'Horários de aula e calendário escolar' },
    { icone: ScrollText, texto: 'Histórico, documentos e biblioteca' },
];

/**
 * Layout das telas de acesso (login, recuperar senha...).
 */
export default function AuthLayout({ children, title, description }: AuthLayoutProps) {
    const { portal, flash } = usePage<Partial<SharedData>>().props;

    return (
        <div className="bg-background grid min-h-svh lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            {/* Painel institucional (computador) */}
            <aside className="relative hidden overflow-hidden bg-gradient-to-br from-[hsl(221,83%,42%)] to-[hsl(232,70%,28%)] p-12 text-white lg:flex lg:flex-col">
                <div aria-hidden className="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full bg-white/10" />
                <div aria-hidden className="pointer-events-none absolute -bottom-32 -left-16 size-80 rounded-full bg-white/5" />

                <div className="relative flex items-center gap-3">
                    <PortalLogo className="size-11 bg-white/15 text-white" />
                    <div className="leading-tight">
                        <p className="font-bold">Portal do Aluno</p>
                        {portal && <p className="text-sm text-white/75">{portal.prefeitura}</p>}
                    </div>
                </div>

                <div className="relative my-auto max-w-md">
                    <h2 className="text-4xl leading-tight font-bold tracking-tight text-balance">Sua vida escolar, sempre à mão.</h2>
                    <p className="mt-4 text-lg text-white/80">Acompanhe tudo da escola pelo computador ou pelo celular.</p>

                    <ul className="mt-10 space-y-4">
                        {destaques.map(({ icone: Icone, texto }) => (
                            <li key={texto} className="flex items-center gap-3">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-white/15">
                                    <Icone className="size-5" />
                                </span>
                                <span className="font-medium">{texto}</span>
                            </li>
                        ))}
                    </ul>
                </div>

                {portal && <p className="relative text-sm text-white/60">{portal.orgao}</p>}
            </aside>

            {/* Formulário */}
            <main className="flex flex-col items-center justify-center px-6 py-10 sm:px-10">
                <div className="w-full max-w-sm">
                    <Link href={route('home')} className="mb-10 flex items-center gap-3 lg:hidden">
                        <PortalLogo className="size-11" />
                        <div className="leading-tight">
                            <p className="font-bold">Portal do Aluno</p>
                            {portal && <p className="text-muted-foreground text-sm">{portal.orgao}</p>}
                        </div>
                    </Link>

                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground mt-2 text-sm text-pretty">{description}</p>}
                    </div>

                    {flash?.error && (
                        <Notice tone="danger" className="mb-6">
                            {flash.error}
                        </Notice>
                    )}
                    {flash?.success && (
                        <Notice tone="success" className="mb-6">
                            {flash.success}
                        </Notice>
                    )}
                    {flash?.info && (
                        <Notice tone="info" className="mb-6">
                            {flash.info}
                        </Notice>
                    )}

                    {children}
                </div>
            </main>
        </div>
    );
}
