import { PortalLogo } from '@/components/portal/portal-brand';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CloudOff, Hourglass, type LucideIcon, RefreshCw, SearchX, ServerCrash, ShieldX } from 'lucide-react';
import { useState } from 'react';

interface ErroProps {
    status: number;
    /** Falha de comunicação com o e-cidade */
    ecidade?: boolean;
}

const mensagens: Record<number, { titulo: string; texto: string; icone: LucideIcon; tom: string }> = {
    403: {
        titulo: 'Acesso não permitido',
        texto: 'Você não tem permissão para ver esta página.',
        icone: ShieldX,
        tom: 'bg-danger-soft text-danger-ink',
    },
    404: {
        titulo: 'Página não encontrada',
        texto: 'O endereço pode ter mudado ou não existe mais. Confira o link ou volte ao início.',
        icone: SearchX,
        tom: 'bg-accent text-accent-foreground',
    },
    429: {
        titulo: 'Muitas tentativas',
        texto: 'Aguarde um instante antes de tentar novamente.',
        icone: Hourglass,
        tom: 'bg-warning-soft text-warning-ink',
    },
    500: {
        titulo: 'Algo deu errado',
        texto: 'Tivemos um problema ao carregar esta página. Tente novamente em alguns instantes.',
        icone: ServerCrash,
        tom: 'bg-danger-soft text-danger-ink',
    },
    503: {
        titulo: 'Portal em manutenção',
        texto: 'Estamos fazendo melhorias. Volte daqui a pouco.',
        icone: Hourglass,
        tom: 'bg-warning-soft text-warning-ink',
    },
};

const ecidadeFora = {
    titulo: 'Sistema da Educação indisponível',
    texto: 'Não conseguimos falar com o sistema da Secretaria de Educação agora. Seus dados estão seguros — tente novamente em alguns minutos.',
    icone: CloudOff,
    tom: 'bg-warning-soft text-warning-ink',
};

export default function Erro({ status, ecidade = false }: ErroProps) {
    const logado = !!(usePage().props as { auth?: { user?: unknown } }).auth?.user;
    const [recarregando, setRecarregando] = useState(false);
    const msg = ecidade ? ecidadeFora : (mensagens[status] ?? mensagens[500]);
    const podeRecarregar = ecidade || status >= 500;

    return (
        <main className="bg-background flex min-h-svh flex-col items-center justify-center px-6 py-12">
            <Head title={msg.titulo} />

            <div className="flex w-full max-w-md flex-col items-center text-center">
                <PortalLogo className="mb-10 size-11" />

                <div className={cn('mb-6 flex size-20 items-center justify-center rounded-3xl', msg.tom)}>
                    <msg.icone className="size-10" />
                </div>

                {!ecidade && <p className="text-muted-foreground mb-2 text-sm font-semibold tracking-widest">ERRO {status}</p>}
                <h1 className="text-2xl font-bold tracking-tight text-balance">{msg.titulo}</h1>
                <p className="text-muted-foreground mt-3 text-pretty">{msg.texto}</p>

                <div className="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    {podeRecarregar && (
                        <Button
                            onClick={() => router.reload({ onStart: () => setRecarregando(true), onFinish: () => setRecarregando(false) })}
                            disabled={recarregando}
                        >
                            <RefreshCw className={recarregando ? 'animate-spin' : ''} />
                            Tentar novamente
                        </Button>
                    )}
                    <Button asChild variant={podeRecarregar ? 'outline' : 'default'}>
                        <Link href={logado ? route('dashboard') : route('login')}>
                            <ArrowLeft />
                            {logado ? 'Voltar ao início' : 'Ir para o login'}
                        </Link>
                    </Button>
                </div>
            </div>
        </main>
    );
}
