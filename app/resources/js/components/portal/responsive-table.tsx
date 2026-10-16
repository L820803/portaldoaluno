import { cn } from '@/lib/utils';
import * as React from 'react';

export interface Column<T> {
    key: string;
    header: React.ReactNode;
    cell: (row: T) => React.ReactNode;
    align?: 'left' | 'center' | 'right';
    /** Destaque no cartão do celular (vira o título do cartão) */
    primary?: boolean;
    /** Não mostra no cartão do celular */
    hideOnMobile?: boolean;
    className?: string;
}

interface ResponsiveTableProps<T> {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T, index: number) => React.Key;
    caption?: string;
    className?: string;
    rowClassName?: (row: T) => string | undefined;
}

const alinhamento = { left: 'text-left', center: 'text-center', right: 'text-right' };

/**
 * Tabela no computador, lista de cartões no celular (evita rolagem horizontal).
 */
export function ResponsiveTable<T>({ columns, rows, rowKey, caption, className, rowClassName }: ResponsiveTableProps<T>) {
    const principal = columns.find((c) => c.primary);
    const demais = columns.filter((c) => c !== principal && !c.hideOnMobile);

    return (
        <div className={className}>
            {/* Computador */}
            <div className="hidden overflow-x-auto md:block">
                <table className="w-full text-sm">
                    {caption && <caption className="sr-only">{caption}</caption>}
                    <thead>
                        <tr className="border-b">
                            {columns.map((c) => (
                                <th
                                    key={c.key}
                                    scope="col"
                                    className={cn(
                                        'text-muted-foreground px-4 py-3 text-xs font-semibold tracking-wide uppercase',
                                        alinhamento[c.align ?? 'left'],
                                        c.className,
                                    )}
                                >
                                    {c.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {rows.map((row, i) => (
                            <tr key={rowKey(row, i)} className={cn('hover:bg-muted/50 transition-colors', rowClassName?.(row))}>
                                {columns.map((c) => (
                                    <td key={c.key} className={cn('px-4 py-3', alinhamento[c.align ?? 'left'], c.className)}>
                                        {c.cell(row)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Celular */}
            <ul className="divide-y md:hidden">
                {rows.map((row, i) => (
                    <li key={rowKey(row, i)} className={cn('px-4 py-3', rowClassName?.(row))}>
                        {principal && <div className="mb-2 font-semibold">{principal.cell(row)}</div>}
                        <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            {demais.map((c) => (
                                <div key={c.key} className="min-w-0">
                                    <dt className="text-muted-foreground text-xs">{c.header}</dt>
                                    <dd className="font-medium">{c.cell(row)}</dd>
                                </div>
                            ))}
                        </dl>
                    </li>
                ))}
            </ul>
        </div>
    );
}
