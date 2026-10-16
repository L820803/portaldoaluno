import { Email, Telefone } from '@/components/portal/contato';
import { CopyButton } from '@/components/portal/copy-button';
import { FichaPage } from '@/components/portal/ficha-page';
import { InfoGrid } from '@/components/portal/info-grid';
import { SectionCard } from '@/components/portal/section-card';
import { StatusBadge } from '@/components/portal/status-badge';
import { formatarData, fraseCase, titleCase } from '@/lib/format';
import { type OutrasInformacoes as Dados, type FichaProps } from '@/types/aluno';
import { Bus, HandHeart, Info, MapPinned, NotebookPen, UserRoundCheck } from 'lucide-react';

/**
 * "SIM"/"NÃO" do e-cidade como selo.
 */
function SimNao({ valor }: { valor: string | null }) {
    if (!valor) {
        return null;
    }

    const v = valor.toUpperCase();

    if (v === 'SIM' || v === 'S') {
        return <StatusBadge tone="success">Sim</StatusBadge>;
    }

    if (v === 'NÃO' || v === 'NAO' || v === 'N') {
        return <StatusBadge tone="neutral">Não</StatusBadge>;
    }

    return <>{fraseCase(valor)}</>;
}

export default function OutrasInformacoes(props: FichaProps<Dados>) {
    return (
        <FichaPage title="Outras informações" description="Responsável, programas sociais, transporte escolar e procedência." icon={Info} {...props}>
            {(d) => (
                <>
                    <div className="grid gap-6 lg:grid-cols-2">
                        <SectionCard title="Responsável" icon={UserRoundCheck}>
                            <InfoGrid
                                columns={2}
                                items={[
                                    { label: 'Nome', value: titleCase(d.responsavel.nome), full: true },
                                    { label: 'Celular', value: d.responsavel.celular && <Telefone valor={d.responsavel.celular} /> },
                                    { label: 'E-mail', value: d.responsavel.email && <Email valor={d.responsavel.email} /> },
                                ]}
                            />
                        </SectionCard>

                        <SectionCard title="Contato do aluno" icon={NotebookPen}>
                            <InfoGrid
                                columns={2}
                                items={[
                                    { label: 'Telefone de contato', value: d.contato.telefone && <Telefone valor={d.contato.telefone} /> },
                                    { label: 'E-mail', value: d.contato.email && <Email valor={d.contato.email} /> },
                                    { label: 'Profissão', value: titleCase(d.profissao) },
                                ]}
                            />
                        </SectionCard>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <SectionCard title="Programas sociais" icon={HandHeart}>
                            <InfoGrid
                                columns={2}
                                items={[
                                    { label: 'Bolsa Família', value: d.programas.bolsaFamilia && <SimNao valor={d.programas.bolsaFamilia} /> },
                                    {
                                        label: 'Nº do NIS',
                                        value: d.programas.nis && (
                                            <span className="inline-flex items-center gap-1 font-mono">
                                                {d.programas.nis}
                                                <CopyButton value={d.programas.nis} label="NIS" className="size-6" />
                                            </span>
                                        ),
                                    },
                                ]}
                            />
                        </SectionCard>

                        <SectionCard title="Transporte escolar" icon={Bus}>
                            <InfoGrid
                                columns={2}
                                items={[
                                    { label: 'Usa transporte público', value: d.transporte.publico && <SimNao valor={d.transporte.publico} /> },
                                    { label: 'Poder público responsável', value: titleCase(d.transporte.poderPublico) },
                                    {
                                        label: 'Meios de transporte',
                                        full: true,
                                        value: d.transporte.meios.length > 0 && (
                                            <span className="flex flex-wrap gap-1.5">
                                                {d.transporte.meios.map((m) => (
                                                    <StatusBadge key={m} tone="info">
                                                        {titleCase(m)}
                                                    </StatusBadge>
                                                ))}
                                            </span>
                                        ),
                                    },
                                ]}
                            />
                        </SectionCard>
                    </div>

                    <SectionCard title="Procedência e escolarização" icon={MapPinned}>
                        <InfoGrid
                            items={[
                                { label: 'Local de procedência', value: titleCase(d.procedencia.local) },
                                { label: 'Data de procedência', value: formatarData(d.procedencia.data) },
                                { label: 'Escolarização em outro espaço', value: fraseCase(d.escolarizacaoOutroEspaco) },
                                { label: 'Observações', value: d.observacao, full: true },
                            ]}
                        />
                    </SectionCard>
                </>
            )}
        </FichaPage>
    );
}
