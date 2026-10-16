import { StatusBadge } from '@/components/portal/status-badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { titleCase } from '@/lib/format';
import { type Parecer } from '@/types/aluno';
import { CloudOff, MessageSquareText, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';

export interface ParecerAlvo {
    regencia: number;
    periodo: number;
    disciplina: string | null;
    nomePeriodo: string | null;
}

/**
 * Parecer descritivo, amparo e pareceres padronizados de uma nota
 * (modal "Informações complementares" do legado).
 */
export function ParecerDialog({ alvo, onClose }: { alvo: ParecerAlvo | null; onClose: () => void }) {
    const [parecer, setParecer] = useState<Parecer | null>(null);
    const [erro, setErro] = useState<string | null>(null);

    useEffect(() => {
        if (!alvo) {
            return;
        }

        const controle = new AbortController();
        setParecer(null);
        setErro(null);

        fetch(route('boletim.parecer', { regencia: alvo.regencia, periodo: alvo.periodo }), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            signal: controle.signal,
        })
            .then(async (r) => {
                const corpo = await r.json().catch(() => ({}));

                if (!r.ok) {
                    throw new Error(corpo.message ?? 'Não foi possível carregar o parecer.');
                }

                setParecer(corpo as Parecer);
            })
            .catch((e: Error) => {
                if (e.name !== 'AbortError') {
                    setErro(e.message);
                }
            });

        return () => controle.abort();
    }, [alvo]);

    return (
        <Dialog open={alvo !== null} onOpenChange={(aberto) => !aberto && onClose()}>
            <DialogContent className="max-h-[85svh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{titleCase(alvo?.disciplina)}</DialogTitle>
                    <DialogDescription>
                        {titleCase(alvo?.nomePeriodo)}
                        {parecer?.nota && ` · Nota ${parecer.nota}`}
                    </DialogDescription>
                </DialogHeader>

                {erro ? (
                    <div className="text-danger-ink flex items-center gap-2 text-sm">
                        <CloudOff className="size-4" />
                        {erro}
                    </div>
                ) : !parecer ? (
                    <div className="space-y-2" aria-busy="true">
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-5/6" />
                        <Skeleton className="h-4 w-2/3" />
                    </div>
                ) : (
                    <div className="space-y-5 text-sm">
                        {parecer.parecer && (
                            <section>
                                <h3 className="mb-1.5 flex items-center gap-2 font-semibold">
                                    <MessageSquareText className="text-primary size-4" />
                                    Parecer
                                </h3>
                                <p className="text-muted-foreground leading-relaxed whitespace-pre-line">{parecer.parecer}</p>
                            </section>
                        )}

                        {parecer.amparo && (
                            <section>
                                <h3 className="mb-1.5 flex items-center gap-2 font-semibold">
                                    <ShieldCheck className="text-primary size-4" />
                                    Amparo
                                </h3>
                                <p className="text-muted-foreground">{parecer.amparo}</p>
                            </section>
                        )}

                        {parecer.padronizados.length > 0 && (
                            <section>
                                <h3 className="mb-2 font-semibold">Avaliação por critérios</h3>
                                <ul className="divide-y rounded-lg border">
                                    {parecer.padronizados.map((p) => (
                                        <li key={p.descricao} className="flex items-center justify-between gap-3 px-3 py-2">
                                            <span>{p.descricao}</span>
                                            {p.legenda && <StatusBadge tone="info">{p.legenda}</StatusBadge>}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
