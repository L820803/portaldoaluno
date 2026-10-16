import { cn } from '@/lib/utils';
import { Inbox, type LucideIcon } from 'lucide-react';
import * as React from 'react';

interface EmptyStateProps {
    title: string;
    description?: React.ReactNode;
    icon?: LucideIcon;
    action?: React.ReactNode;
    tone?: 'neutral' | 'danger' | 'warning';
    className?: string;
}

const tons = {
    neutral: 'bg-accent text-accent-foreground',
    warning: 'bg-warning-soft text-warning-ink',
    danger: 'bg-danger-soft text-danger-ink',
};

/**
 * Estado vazio ("Não há horários cadastrados para a matrícula selecionada").
 */
export function EmptyState({ title, description, icon: Icon = Inbox, action, tone = 'neutral', className }: EmptyStateProps) {
    return (
        <div className={cn('flex flex-col items-center justify-center px-6 py-12 text-center', className)}>
            <div className={cn('mb-4 flex size-14 items-center justify-center rounded-2xl', tons[tone])}>
                <Icon className="size-7" />
            </div>
            <h3 className="text-base font-semibold text-balance">{title}</h3>
            {description && <p className="text-muted-foreground mt-1.5 max-w-sm text-sm text-pretty">{description}</p>}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
