import { Skeleton } from '@/components/ui/skeleton';

/**
 * Esqueleto de carregamento (dados adiados / navegação lenta).
 */
export function PageSkeleton({ cards = 3 }: { cards?: number }) {
    return (
        <div aria-busy="true" aria-label="Carregando" className="space-y-6">
            <div className="space-y-2">
                <Skeleton className="h-7 w-56" />
                <Skeleton className="h-4 w-80 max-w-full" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {Array.from({ length: cards }).map((_, i) => (
                    <Skeleton key={i} className="h-28 rounded-xl" />
                ))}
            </div>
            <Skeleton className="h-64 rounded-xl" />
        </div>
    );
}
