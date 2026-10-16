import { cn } from '@/lib/utils';
import { Check, Copy } from 'lucide-react';
import { useState } from 'react';

/**
 * Copia um valor (CPF, NIS, matrícula...) para a área de transferência.
 */
export function CopyButton({ value, label, className }: { value: string; label: string; className?: string }) {
    const [copiado, setCopiado] = useState(false);

    const copiar = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setCopiado(true);
            setTimeout(() => setCopiado(false), 2000);
        } catch {
            // Sem permissão de área de transferência (http ou navegador antigo): nada a fazer
        }
    };

    return (
        <button
            type="button"
            onClick={copiar}
            className={cn(
                'text-muted-foreground hover:text-foreground hover:bg-muted focus-visible:ring-ring inline-flex size-7 items-center justify-center rounded-md align-middle outline-hidden transition-colors focus-visible:ring-2',
                copiado && 'text-success-ink',
                className,
            )}
            aria-label={copiado ? `${label} copiado` : `Copiar ${label}`}
            title={copiado ? 'Copiado!' : 'Copiar'}
        >
            {copiado ? <Check className="size-4" /> : <Copy className="size-4" />}
        </button>
    );
}
