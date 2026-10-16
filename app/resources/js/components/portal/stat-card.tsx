import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';
import * as React from 'react';

interface StatCardProps {
    label: string;
    value: React.ReactNode;
    hint?: React.ReactNode;
    icon?: LucideIcon;
    tone?: 'info' | 'success' | 'warning' | 'danger';
    className?: string;
}

const tons = {
    info: 'bg-info-soft text-info-ink',
    success: 'bg-success-soft text-success-ink',
    warning: 'bg-warning-soft text-warning-ink',
    danger: 'bg-danger-soft text-danger-ink',
};

const tonsValor = {
    info: '',
    success: 'max-sm:text-success-ink',
    warning: 'max-sm:text-warning-ink',
    danger: 'max-sm:text-danger-ink',
};

/**
 * Indicador numérico (frequência, faltas, média...).
 */
export function StatCard({ label, value, hint, icon: Icon, tone = 'info', className }: StatCardProps) {
    return (
        <div className={cn('bg-card flex flex-col rounded-xl border p-3 shadow-xs sm:p-5', className)}>
            <div className="flex items-start justify-between gap-3">
                <p className="text-muted-foreground text-xs leading-tight font-medium sm:text-sm">{label}</p>
                {Icon && (
                    <div className={cn('hidden size-9 shrink-0 items-center justify-center rounded-lg sm:flex', tons[tone])}>
                        <Icon className="size-[18px]" />
                    </div>
                )}
            </div>
            {/* No celular a cor do tom vai no próprio número (sem espaço para o ícone) */}
            <p className={cn('mt-auto pt-2 text-xl font-bold tracking-tight tabular-nums sm:text-3xl', tonsValor[tone])}>{value}</p>
            {hint && <p className="text-muted-foreground mt-1 hidden text-xs sm:block">{hint}</p>}
        </div>
    );
}
