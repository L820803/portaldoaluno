import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { CircleAlert, CircleCheck, CloudOff, Info, X, type LucideIcon } from 'lucide-react';
import * as React from 'react';
import { useEffect, useState } from 'react';

type Tone = 'success' | 'danger' | 'info' | 'warning';

const tons: Record<Tone, { classe: string; icone: LucideIcon }> = {
    success: { classe: 'bg-success-soft text-success-ink border-success/20', icone: CircleCheck },
    danger: { classe: 'bg-danger-soft text-danger-ink border-danger/20', icone: CircleAlert },
    warning: { classe: 'bg-warning-soft text-warning-ink border-warning/30', icone: CloudOff },
    info: { classe: 'bg-info-soft text-info-ink border-info/20', icone: Info },
};

interface NoticeProps {
    tone?: Tone;
    title?: React.ReactNode;
    children?: React.ReactNode;
    icon?: LucideIcon;
    onClose?: () => void;
    className?: string;
}

/**
 * Faixa de aviso colorida.
 */
export function Notice({ tone = 'info', title, children, icon, onClose, className }: NoticeProps) {
    const Icone = icon ?? tons[tone].icone;

    return (
        <div
            role={tone === 'danger' ? 'alert' : 'status'}
            className={cn('flex items-start gap-3 rounded-xl border px-4 py-3 text-sm', tons[tone].classe, className)}
        >
            <Icone className="mt-0.5 size-[18px] shrink-0" />
            <div className="min-w-0 flex-1">
                {title && <p className="font-semibold">{title}</p>}
                {children && <div className={cn(title && 'mt-0.5 opacity-90')}>{children}</div>}
            </div>
            {onClose && (
                <button type="button" onClick={onClose} className="-m-1 rounded-md p-1 opacity-70 hover:opacity-100" aria-label="Fechar aviso">
                    <X className="size-4" />
                </button>
            )}
        </div>
    );
}

/**
 * Mensagens de sessão (flash) e aviso de e-cidade indisponível.
 */
export function PortalNotices() {
    const { flash, aluno } = usePage<SharedData>().props;
    const [fechados, setFechados] = useState<string[]>([]);

    // Nova mensagem do servidor volta a aparecer
    useEffect(() => setFechados([]), [flash]);

    const mensagens = (
        [
            ['success', flash.success],
            ['danger', flash.error],
            ['info', flash.info],
        ] as const
    ).filter(([, texto]) => texto && !fechados.includes(texto));

    if (mensagens.length === 0 && !aluno?.indisponivel) {
        return null;
    }

    return (
        <div className="mb-6 space-y-3">
            {aluno?.indisponivel && (
                <Notice tone="warning" title="Alguns dados podem não aparecer agora">
                    O sistema da Secretaria de Educação está fora do ar no momento. Tente novamente em alguns minutos.
                </Notice>
            )}
            {mensagens.map(([tom, texto]) => (
                <Notice key={texto} tone={tom} onClose={() => setFechados((f) => [...f, texto!])}>
                    {texto}
                </Notice>
            ))}
        </div>
    );
}
