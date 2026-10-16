import InputError from '@/components/input-error';
import { EmptyState } from '@/components/portal/empty-state';
import { FichaPage } from '@/components/portal/ficha-page';
import { ResponsiveTable } from '@/components/portal/responsive-table';
import { SectionCard } from '@/components/portal/section-card';
import { StatCard } from '@/components/portal/stat-card';
import { StatusBadge } from '@/components/portal/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatarData, titleCase } from '@/lib/format';
import { type Biblioteca as BibliotecaDados, type FichaProps } from '@/types/aluno';
import { router, usePage } from '@inertiajs/react';
import { BookCheck, BookOpen, Filter, LibraryBig, LoaderCircle, TriangleAlert, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface BibliotecaProps extends FichaProps<BibliotecaDados> {
    filtro: { de: string | null; ate: string | null };
}

const situacoes = {
    emprestado: { texto: 'Com você', tom: 'info' },
    atrasado: { texto: 'Atrasado', tom: 'danger' },
    devolvido: { texto: 'Devolvido', tom: 'success' },
} as const;

function FiltroPeriodo({ filtro }: { filtro: BibliotecaProps['filtro'] }) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [de, setDe] = useState(filtro.de ?? '');
    const [ate, setAte] = useState(filtro.ate ?? '');
    const [carregando, setCarregando] = useState(false);

    const aplicar = (dados: { de?: string; ate?: string }) =>
        router.get(route('biblioteca'), dados, {
            preserveScroll: true,
            onStart: () => setCarregando(true),
            onFinish: () => setCarregando(false),
        });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        aplicar({ ...(de && { de }), ...(ate && { ate }) });
    };

    return (
        <SectionCard title="Período de retirada" icon={Filter}>
            <form onSubmit={enviar} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div className="grid flex-1 gap-1.5">
                    <Label htmlFor="de">De</Label>
                    <Input id="de" type="date" value={de} onChange={(e) => setDe(e.target.value)} />
                    <InputError message={errors.de} />
                </div>
                <div className="grid flex-1 gap-1.5">
                    <Label htmlFor="ate">Até</Label>
                    <Input id="ate" type="date" value={ate} min={de || undefined} onChange={(e) => setAte(e.target.value)} />
                    <InputError message={errors.ate} />
                </div>
                <div className="flex gap-2">
                    <Button type="submit" disabled={carregando}>
                        {carregando ? <LoaderCircle className="size-4 animate-spin" /> : <Filter className="size-4" />}
                        Filtrar
                    </Button>
                    {(filtro.de || filtro.ate) && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setDe('');
                                setAte('');
                                aplicar({});
                            }}
                        >
                            <X className="size-4" />
                            Limpar
                        </Button>
                    )}
                </div>
            </form>
        </SectionCard>
    );
}

export default function Biblioteca({ filtro, ...props }: BibliotecaProps) {
    return (
        <FichaPage title="Biblioteca" description="Livros que você retirou e as datas de devolução." icon={LibraryBig} rodape={false} {...props}>
            {(b) => {
                const comVoce = b.emprestimos.filter((e) => e.situacao !== 'devolvido').length;
                const atrasados = b.emprestimos.filter((e) => e.situacao === 'atrasado').length;

                return (
                    <>
                        <div className="grid grid-cols-3 gap-2 sm:gap-4">
                            <StatCard label="Com você" value={comVoce} icon={BookOpen} tone="info" />
                            <StatCard label="Atrasados" value={atrasados} icon={TriangleAlert} tone={atrasados > 0 ? 'danger' : 'success'} />
                            <StatCard label="Retiradas no período" value={b.emprestimos.length} icon={BookCheck} tone="info" />
                        </div>

                        <FiltroPeriodo filtro={filtro} />

                        <SectionCard title="Empréstimos" icon={LibraryBig} flush>
                            {b.emprestimos.length === 0 ? (
                                <EmptyState
                                    icon={LibraryBig}
                                    title="Nenhum empréstimo encontrado"
                                    description={
                                        filtro.de || filtro.ate ? 'Tente outro período de retirada.' : 'Os livros que você retirar aparecem aqui.'
                                    }
                                />
                            ) : (
                                <ResponsiveTable
                                    className="border-t"
                                    caption="Empréstimos da biblioteca"
                                    rows={b.emprestimos}
                                    rowKey={(e, i) => `${e.titulo}-${e.retirada}-${i}`}
                                    columns={[
                                        {
                                            key: 'titulo',
                                            header: 'Livro',
                                            primary: true,
                                            cell: (e) => <span className="font-medium">{titleCase(e.titulo)}</span>,
                                        },
                                        { key: 'bib', header: 'Biblioteca', cell: (e) => titleCase(e.biblioteca) || '—' },
                                        { key: 'ret', header: 'Retirada', align: 'center', cell: (e) => formatarData(e.retirada) || '—' },
                                        { key: 'prev', header: 'Devolver até', align: 'center', cell: (e) => formatarData(e.devolverAte) || '—' },
                                        { key: 'dev', header: 'Devolvido em', align: 'center', cell: (e) => formatarData(e.devolvidoEm) || '—' },
                                        {
                                            key: 'sit',
                                            header: 'Situação',
                                            align: 'center',
                                            cell: (e) => (
                                                <StatusBadge tone={situacoes[e.situacao].tom} dot>
                                                    {situacoes[e.situacao].texto}
                                                </StatusBadge>
                                            ),
                                        },
                                    ]}
                                />
                            )}
                        </SectionCard>
                    </>
                );
            }}
        </FichaPage>
    );
}
