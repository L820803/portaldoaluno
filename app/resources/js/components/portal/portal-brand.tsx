import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';

/**
 * Ícone do portal (estudante).
 */
export function PortalLogo({ className }: { className?: string }) {
    return (
        <div className={cn('bg-primary text-primary-foreground flex size-9 shrink-0 items-center justify-center rounded-lg', className)}>
            <GraduationCap className="size-5" />
        </div>
    );
}

/**
 * Logo + "Portal do Aluno" + órgão.
 */
export function PortalBrand({ className, compact = false }: { className?: string; compact?: boolean }) {
    const portal = usePage<Partial<SharedData>>().props.portal;

    return (
        <div className={cn('flex min-w-0 items-center gap-2.5', className)}>
            <PortalLogo />
            <div className="grid min-w-0 leading-tight">
                <span className="truncate text-sm font-bold">Portal do Aluno</span>
                {!compact && portal && <span className="text-muted-foreground truncate text-xs">{portal.orgao}</span>}
            </div>
        </div>
    );
}
