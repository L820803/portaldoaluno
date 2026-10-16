import { AlunoAvatar } from '@/components/portal/aluno-avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { titleCase } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, KeyRound, LogOut, Palette, UserCog } from 'lucide-react';

/**
 * Nome e foto do aluno logado.
 */
export function useUsuario() {
    const { auth, aluno } = usePage<SharedData>().props;

    return {
        nome: titleCase(aluno?.nome ?? auth.user.name),
        foto: aluno?.foto ?? null,
        identificacao: aluno ? `Código ${aluno.codigo}` : (auth.user.email ?? auth.user.login),
    };
}

/**
 * Avatar com menu: conta, senha, aparência e sair.
 */
export function UserMenu({ showName = false, className }: { showName?: boolean; className?: string }) {
    const { nome, foto, identificacao } = useUsuario();
    const cleanup = useMobileNavigation();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={cn(
                    'hover:bg-muted focus-visible:ring-ring flex items-center gap-2 rounded-full p-0.5 outline-hidden transition-colors focus-visible:ring-2',
                    showName && 'rounded-lg py-1 pr-2 pl-1',
                    className,
                )}
                aria-label="Menu da conta"
            >
                <AlunoAvatar nome={nome} foto={foto} />
                {showName && (
                    <>
                        <span className="hidden max-w-40 truncate text-sm font-medium lg:block">{nome}</span>
                        <ChevronDown className="text-muted-foreground hidden size-4 lg:block" />
                    </>
                )}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64 rounded-xl">
                <DropdownMenuLabel className="flex items-center gap-3 p-2 font-normal">
                    <AlunoAvatar nome={nome} foto={foto} className="size-10" />
                    <div className="grid min-w-0 leading-tight">
                        <span className="truncate text-sm font-semibold">{nome}</span>
                        <span className="text-muted-foreground truncate text-xs">{identificacao}</span>
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                    <DropdownMenuItem asChild>
                        <Link className="w-full" href={route('profile.edit')} prefetch onClick={cleanup}>
                            <UserCog />
                            Minha conta
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link className="w-full" href={route('password.edit')} onClick={cleanup}>
                            <KeyRound />
                            Alterar senha
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link className="w-full" href={route('appearance')} onClick={cleanup}>
                            <Palette />
                            Aparência
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link className="w-full" method="post" href={route('logout')} as="button" onClick={cleanup}>
                        <LogOut />
                        Sair
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
