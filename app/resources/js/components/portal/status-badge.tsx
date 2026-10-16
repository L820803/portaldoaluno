import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

const statusBadgeVariants = cva('inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap', {
    variants: {
        tone: {
            success: 'bg-success-soft text-success-ink',
            warning: 'bg-warning-soft text-warning-ink',
            danger: 'bg-danger-soft text-danger-ink',
            info: 'bg-info-soft text-info-ink',
            neutral: 'bg-muted text-muted-foreground',
        },
    },
    defaultVariants: { tone: 'neutral' },
});

const dotColor = {
    success: 'bg-success',
    warning: 'bg-warning',
    danger: 'bg-danger',
    info: 'bg-info',
    neutral: 'bg-muted-foreground',
} as const;

export type StatusTone = NonNullable<VariantProps<typeof statusBadgeVariants>['tone']>;

interface StatusBadgeProps extends React.HTMLAttributes<HTMLSpanElement>, VariantProps<typeof statusBadgeVariants> {
    /** Mostra um ponto colorido antes do texto */
    dot?: boolean;
}

/**
 * Selo de situação: aprovado (success), atenção (warning), reprovado/atrasado (danger)...
 */
export function StatusBadge({ tone = 'neutral', dot = false, className, children, ...props }: StatusBadgeProps) {
    return (
        <span className={cn(statusBadgeVariants({ tone }), className)} {...props}>
            {dot && <span aria-hidden className={cn('size-1.5 rounded-full', dotColor[tone ?? 'neutral'])} />}
            {children}
        </span>
    );
}

/**
 * Tom do selo a partir dos termos de situação do e-cidade.
 */
export function toneDaSituacao(situacao: string | null | undefined): StatusTone {
    const s = (situacao ?? '').toUpperCase();

    if (/REPROV|RETID|EVAD|CANCEL|ABANDON|ATRAS/.test(s)) {
        return 'danger';
    }

    if (/APROV|CONCLU|MATRICULAD|DEVOLVID/.test(s)) {
        return 'success';
    }

    if (/ANDAMENTO|RECUPERA|PROGRESS|PENDENTE|TRANSFER/.test(s)) {
        return 'warning';
    }

    return 'neutral';
}
