import { formatarTelefone, linhasEndereco, linkMapa, linkTelefone, type Endereco } from '@/lib/format';
import { cn } from '@/lib/utils';
import { ExternalLink, Mail, Phone } from 'lucide-react';

const linkClass = 'text-primary inline-flex items-center gap-1.5 font-medium underline-offset-4 hover:underline';

/**
 * Telefone clicável (abre o discador no celular).
 */
export function Telefone({ valor, className }: { valor: string | null; className?: string }) {
    const href = linkTelefone(valor);

    if (!valor) {
        return null;
    }

    return href ? (
        <a href={href} className={cn(linkClass, className)}>
            <Phone className="size-3.5" />
            {formatarTelefone(valor)}
        </a>
    ) : (
        <span className={className}>{valor}</span>
    );
}

/**
 * E-mail clicável.
 */
export function Email({ valor, className }: { valor: string | null; className?: string }) {
    if (!valor) {
        return null;
    }

    return (
        <a href={`mailto:${valor}`} className={cn(linkClass, 'break-all', className)}>
            <Mail className="size-3.5 shrink-0" />
            {valor}
        </a>
    );
}

/**
 * Endereço em linhas, com link "Ver no mapa".
 */
export function EnderecoBloco({ endereco, extra }: { endereco: Endereco; extra?: string | null }) {
    const linhas = linhasEndereco(endereco);
    const mapa = linkMapa(endereco);

    if (linhas.length === 0) {
        return <p className="text-muted-foreground text-sm">Endereço não informado.</p>;
    }

    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <address className="text-sm leading-relaxed not-italic">
                    {linhas.map((l, i) => (
                        <span key={l} className={cn('block', i === 0 ? 'font-semibold' : 'text-muted-foreground')}>
                            {l}
                        </span>
                    ))}
                    {extra && <span className="text-muted-foreground block">{extra}</span>}
                </address>
            </div>
            {mapa && (
                <a
                    href={mapa}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="hover:bg-muted inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors"
                >
                    <ExternalLink className="size-4" />
                    Ver no mapa
                </a>
            )}
        </div>
    );
}
