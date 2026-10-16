import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { rotuloMatricula } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { BookMarked, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

/**
 * Escolhe a matrícula consultada pelas telas (boletim, faltas, horários...).
 * Com uma única matrícula, mostra apenas o rótulo.
 */
export function MatriculaSelector({ className }: { className?: string }) {
    const { aluno } = usePage<SharedData>().props;
    const [trocando, setTrocando] = useState(false);

    if (!aluno || aluno.matriculas.length === 0 || aluno.matriculaAtiva === null) {
        return null;
    }

    const ativa = aluno.matriculas.find((m) => m.codigo === aluno.matriculaAtiva)!;
    const icone = trocando ? <LoaderCircle className="size-4 shrink-0 animate-spin" /> : <BookMarked className="text-primary size-4 shrink-0" />;

    if (aluno.matriculas.length === 1) {
        return (
            <div className={cn('bg-muted/60 flex h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium', className)}>
                {icone}
                <span className="truncate">{rotuloMatricula(ativa)}</span>
            </div>
        );
    }

    const trocar = (valor: string) => {
        router.put(
            route('matricula.ativa'),
            { matricula: Number(valor) },
            {
                preserveScroll: true,
                onStart: () => setTrocando(true),
                onFinish: () => setTrocando(false),
            },
        );
    };

    return (
        <Select value={String(aluno.matriculaAtiva)} onValueChange={trocar} disabled={trocando}>
            <SelectTrigger
                aria-label="Matrícula consultada"
                className={cn('bg-muted/60 h-9 gap-2 rounded-lg border-transparent font-medium', className)}
            >
                {icone}
                <SelectValue />
            </SelectTrigger>
            <SelectContent align="end">
                {[...aluno.matriculas].reverse().map((m) => (
                    <SelectItem key={m.codigo} value={String(m.codigo)}>
                        {rotuloMatricula(m)}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
