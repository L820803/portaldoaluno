import { ouTraco } from '@/lib/format';
import { cn } from '@/lib/utils';
import * as React from 'react';

export interface InfoItem {
    label: string;
    value: React.ReactNode;
    /** Ocupa a linha inteira (endereços, observações) */
    full?: boolean;
}

interface InfoGridProps {
    items: InfoItem[];
    /** Colunas a partir de telas médias */
    columns?: 2 | 3 | 4;
    /** Esconde itens sem valor em vez de mostrar "—" */
    hideEmpty?: boolean;
    className?: string;
}

const colunas = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-2 lg:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-4',
};

const vazio = (v: React.ReactNode) => v === null || v === undefined || (typeof v === 'string' && v.trim() === '');

/**
 * Lista de pares rótulo/valor (substitui as tabelas "th: td" do legado).
 */
export function InfoGrid({ items, columns = 3, hideEmpty = false, className }: InfoGridProps) {
    const visiveis = hideEmpty ? items.filter((i) => !vazio(i.value)) : items;

    return (
        <dl className={cn('grid grid-cols-1 gap-x-6 gap-y-4', colunas[columns], className)}>
            {visiveis.map((item) => (
                <div key={item.label} className={cn('min-w-0', item.full && 'sm:col-span-full')}>
                    <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{item.label}</dt>
                    <dd className={cn('mt-1 text-sm font-medium break-words', vazio(item.value) && 'text-muted-foreground')}>
                        {vazio(item.value) ? ouTraco(null) : item.value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
