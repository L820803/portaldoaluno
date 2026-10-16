import { EmptyState } from '@/components/portal/empty-state';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { CloudOff, RefreshCw } from 'lucide-react';
import { useState } from 'react';

interface ErrorStateProps {
    title?: string;
    description?: string;
    className?: string;
}

/**
 * Falha ao carregar dados do e-cidade dentro de uma página, com "tentar novamente".
 */
export function ErrorState({
    title = 'Não foi possível carregar estas informações',
    description = 'O sistema da Secretaria de Educação não respondeu. Tente novamente em alguns instantes.',
    className,
}: ErrorStateProps) {
    const [carregando, setCarregando] = useState(false);

    return (
        <EmptyState
            tone="danger"
            icon={CloudOff}
            title={title}
            description={description}
            className={className}
            action={
                <Button
                    variant="outline"
                    disabled={carregando}
                    onClick={() => router.reload({ onStart: () => setCarregando(true), onFinish: () => setCarregando(false) })}
                >
                    <RefreshCw className={carregando ? 'animate-spin' : ''} />
                    Tentar novamente
                </Button>
            }
        />
    );
}
