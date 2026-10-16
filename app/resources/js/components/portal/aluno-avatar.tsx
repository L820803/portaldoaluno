import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { titleCase } from '@/lib/format';
import { cn } from '@/lib/utils';

interface AlunoAvatarProps {
    nome: string;
    foto?: string | null;
    className?: string;
}

/**
 * Foto do aluno (e-cidade) com as iniciais como alternativa.
 */
export function AlunoAvatar({ nome, foto, className }: AlunoAvatarProps) {
    const getInitials = useInitials();
    const nomeFormatado = titleCase(nome);

    return (
        <Avatar className={cn('size-9 overflow-hidden rounded-full', className)}>
            {foto && <AvatarImage src={foto} alt={nomeFormatado} className="object-cover" />}
            <AvatarFallback className="bg-accent text-accent-foreground rounded-full text-sm font-semibold">
                {getInitials(nomeFormatado)}
            </AvatarFallback>
        </Avatar>
    );
}
