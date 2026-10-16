import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';
import * as React from 'react';

interface SectionCardProps extends Omit<React.HTMLAttributes<HTMLElement>, 'title'> {
    title?: React.ReactNode;
    description?: React.ReactNode;
    icon?: LucideIcon;
    /** Ação no canto do cabeçalho (link "ver tudo", botão...) */
    action?: React.ReactNode;
    /** Remove o espaçamento interno do conteúdo (tabelas que vão até a borda) */
    flush?: boolean;
}

/**
 * Cartão de seção: agrupa um bloco de informações com título e ícone.
 */
export function SectionCard({ title, description, icon: Icon, action, flush = false, className, children, ...props }: SectionCardProps) {
    return (
        <section className={cn('bg-card text-card-foreground rounded-xl border shadow-xs', className)} {...props}>
            {(title || action) && (
                <header className="flex items-start justify-between gap-3 px-5 pt-5 pb-4">
                    <div className="flex min-w-0 items-center gap-3">
                        {Icon && (
                            <div className="bg-accent text-accent-foreground flex size-9 shrink-0 items-center justify-center rounded-lg">
                                <Icon className="size-[18px]" />
                            </div>
                        )}
                        <div className="min-w-0">
                            {title && <h2 className="leading-tight font-semibold">{title}</h2>}
                            {description && <p className="text-muted-foreground mt-0.5 text-sm">{description}</p>}
                        </div>
                    </div>
                    {action && <div className="shrink-0">{action}</div>}
                </header>
            )}
            <div className={cn(!flush && 'px-5 pb-5', !flush && !(title || action) && 'pt-5')}>{children}</div>
        </section>
    );
}
