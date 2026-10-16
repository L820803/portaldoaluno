import { MatriculaSelector } from '@/components/portal/matricula-selector';
import { PortalBrand } from '@/components/portal/portal-brand';
import { UserMenu } from '@/components/portal/user-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

/**
 * Barra superior fixa. No computador: botão da barra lateral, matrícula e conta.
 * No celular: marca e conta, com a matrícula numa segunda linha.
 */
export function PortalHeader() {
    const { aluno } = usePage<SharedData>().props;
    const temMatricula = !!aluno && aluno.matriculas.length > 0;

    return (
        <>
            <header className="bg-background/85 supports-[backdrop-filter]:bg-background/70 sticky top-0 z-20 border-b backdrop-blur">
                <div className="flex h-16 items-center gap-3 px-4 md:px-6">
                    <SidebarTrigger className="-ml-1 hidden md:inline-flex" aria-label="Recolher ou expandir o menu" />

                    <Link href={route('dashboard')} className="min-w-0 md:hidden" aria-label="Início">
                        <PortalBrand compact />
                    </Link>

                    <div className="ml-auto flex items-center gap-2">
                        {temMatricula && <MatriculaSelector className="hidden w-auto md:flex" />}
                        <UserMenu showName />
                    </div>
                </div>
            </header>

            {/* Celular: fora da barra fixa, para não ocupar a tela ao rolar */}
            {temMatricula && (
                <div className="border-b px-4 py-3 md:hidden">
                    <MatriculaSelector className="w-full" />
                </div>
            )}
        </>
    );
}
