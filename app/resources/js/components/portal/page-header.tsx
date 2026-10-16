import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';
import * as React from 'react';

interface PageHeaderProps {
    title: string;
    description?: React.ReactNode;
    icon?: LucideIcon;
    /** Botões/ações à direita (abaixo do título no celular) */
    actions?: React.ReactNode;
    className?: string;
}

/**
 * Título da página com ícone, descrição e ações.
 */
export function PageHeader({ title, description, icon: Icon, actions, className }: PageHeaderProps) {
    return (
        <div className={cn('mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between', className)}>
            <div className="flex items-start gap-3">
                {Icon && (
                    <div className="bg-accent text-accent-foreground hidden size-11 shrink-0 items-center justify-center rounded-xl sm:flex">
                        <Icon className="size-5" />
                    </div>
                )}
                <div className="min-w-0">
                    <h1 className="text-2xl font-bold tracking-tight text-balance">{title}</h1>
                    {description && <p className="text-muted-foreground mt-1 text-sm text-pretty">{description}</p>}
                </div>
            </div>
            {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
        </div>
    );
}
