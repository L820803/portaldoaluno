import { useSidebar } from '@/components/ui/sidebar';
import { ativo, navegacaoInferior } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';

const itemClass = 'flex flex-1 flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors';

/**
 * Navegação principal no celular, ao alcance do polegar.
 * O botão "Menu" abre a barra lateral completa como gaveta.
 */
export function BottomNav() {
    const { url } = usePage();
    const { setOpenMobile, openMobile } = useSidebar();

    return (
        <nav
            aria-label="Navegação principal"
            className="bg-background/95 supports-[backdrop-filter]:bg-background/80 fixed inset-x-0 bottom-0 z-30 border-t pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
        >
            <div className="flex h-16 items-stretch">
                {navegacaoInferior.map((item) => {
                    const atual = ativo(item, url);

                    return (
                        <Link
                            key={item.route}
                            href={route(item.route)}
                            prefetch
                            aria-current={atual ? 'page' : undefined}
                            className={cn(itemClass, atual ? 'text-primary' : 'text-muted-foreground hover:text-foreground')}
                        >
                            <span className={cn('flex h-7 w-12 items-center justify-center rounded-full transition-colors', atual && 'bg-accent')}>
                                <item.icon className="size-5" />
                            </span>
                            {item.short ?? item.title}
                        </Link>
                    );
                })}

                <button
                    type="button"
                    onClick={() => setOpenMobile(true)}
                    aria-expanded={openMobile}
                    className={cn(itemClass, 'text-muted-foreground hover:text-foreground')}
                >
                    <span className="flex h-7 w-12 items-center justify-center rounded-full">
                        <Menu className="size-5" />
                    </span>
                    Menu
                </button>
            </div>
        </nav>
    );
}
