import { EmptyState } from '@/components/portal/empty-state';
import { ErrorState } from '@/components/portal/error-state';
import { PageHeader } from '@/components/portal/page-header';
import { SectionCard } from '@/components/portal/section-card';
import PortalLayout from '@/layouts/portal-layout';
import { type FichaProps } from '@/types/aluno';
import { Head } from '@inertiajs/react';
import { type LucideIcon } from 'lucide-react';
import * as React from 'react';

interface FichaPageProps<T> extends FichaProps<T> {
    title: string;
    description: string;
    icon: LucideIcon;
    /** Mensagem quando o e-cidade não tem os dados */
    vazio?: string;
    vazioDescricao?: string;
    /** Ações no cabeçalho (filtros, imprimir...) */
    actions?: React.ReactNode;
    /** Lembrete de que os dados vêm da escola (telas cadastrais) */
    rodape?: boolean;
    children: (dados: T) => React.ReactNode;
}

/**
 * Esqueleto comum das telas do aluno: título, erro do e-cidade e estado vazio.
 */
export function FichaPage<T>({
    title,
    description,
    icon,
    dados,
    erro,
    vazio,
    vazioDescricao = 'Em caso de dúvida, procure a secretaria da escola.',
    actions,
    rodape = true,
    children,
}: FichaPageProps<T>) {
    return (
        <PortalLayout>
            <Head title={title} />
            <PageHeader title={title} description={description} icon={icon} actions={actions} />

            {erro ? (
                <SectionCard>
                    <ErrorState />
                </SectionCard>
            ) : dados === null ? (
                <SectionCard>
                    <EmptyState icon={icon} title={vazio ?? 'Nenhuma informação cadastrada'} description={vazioDescricao} />
                </SectionCard>
            ) : (
                <div className="space-y-6">{children(dados)}</div>
            )}

            {rodape && !erro && dados !== null && (
                <p className="text-muted-foreground mt-6 text-center text-xs text-pretty">
                    Estas informações vêm do cadastro da escola. Encontrou algo errado? Procure a secretaria para corrigir.
                </p>
            )}
        </PortalLayout>
    );
}
