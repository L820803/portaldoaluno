import { CopyButton } from '@/components/portal/copy-button';
import { FichaPage } from '@/components/portal/ficha-page';
import { InfoGrid } from '@/components/portal/info-grid';
import { SectionCard } from '@/components/portal/section-card';
import { formatarData, titleCase } from '@/lib/format';
import { type Documentos as DocumentosDados, type FichaProps } from '@/types/aluno';
import { BookUser, Car, FileText, IdCard, Plane } from 'lucide-react';
import * as React from 'react';

function Numero({ valor, label }: { valor: string | null; label: string }) {
    if (!valor) {
        return null;
    }

    return (
        <span className="inline-flex items-center gap-1 font-mono tracking-wide">
            {valor}
            <CopyButton value={valor} label={label} className="size-6" />
        </span>
    );
}

function NaoInformado({ children }: { children: React.ReactNode }) {
    return <p className="text-muted-foreground text-sm">{children}</p>;
}

export default function Documentos(props: FichaProps<DocumentosDados>) {
    return (
        <FichaPage title="Documentos" description="CPF, identidade, certidão de nascimento e outros documentos." icon={IdCard} {...props}>
            {(d) => (
                <>
                    {/* CPF em destaque: é o documento mais pedido */}
                    <section className="bg-card flex flex-wrap items-center justify-between gap-4 rounded-xl border p-5 shadow-xs">
                        <div className="flex items-center gap-3">
                            <div className="bg-accent text-accent-foreground flex size-11 items-center justify-center rounded-xl">
                                <IdCard className="size-5" />
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">CPF</p>
                                {d.cpf ? (
                                    <p className="text-xl font-bold">
                                        <Numero valor={d.cpf} label="CPF" />
                                    </p>
                                ) : (
                                    <p className="text-muted-foreground font-medium">Não informado</p>
                                )}
                            </div>
                        </div>
                        {d.passaporte && (
                            <div className="flex items-center gap-3">
                                <Plane className="text-muted-foreground size-5" />
                                <div>
                                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Passaporte</p>
                                    <p className="font-semibold">
                                        <Numero valor={d.passaporte} label="passaporte" />
                                    </p>
                                </div>
                            </div>
                        )}
                    </section>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <SectionCard title="Identidade (RG)" icon={BookUser}>
                            {d.identidade ? (
                                <InfoGrid
                                    columns={2}
                                    items={[
                                        { label: 'Número', value: d.identidade.numero && <Numero valor={d.identidade.numero} label="RG" /> },
                                        { label: 'Complemento', value: d.identidade.complemento },
                                        { label: 'Órgão emissor', value: d.identidade.orgaoEmissor },
                                        { label: 'UF', value: d.identidade.uf },
                                        { label: 'Data de expedição', value: formatarData(d.identidade.expedicao) },
                                    ]}
                                />
                            ) : (
                                <NaoInformado>Identidade não informada.</NaoInformado>
                            )}
                        </SectionCard>

                        <SectionCard title="Certidão de nascimento" icon={FileText}>
                            {d.certidao ? (
                                <InfoGrid
                                    columns={2}
                                    hideEmpty
                                    items={[
                                        { label: 'Modelo', value: titleCase(d.certidao.tipo) },
                                        { label: 'Data de emissão', value: formatarData(d.certidao.emissao) },
                                        {
                                            label: 'Matrícula',
                                            value: d.certidao.matricula && <Numero valor={d.certidao.matricula} label="matrícula da certidão" />,
                                            full: true,
                                        },
                                        { label: 'Termo', value: d.certidao.termo },
                                        { label: 'Livro', value: d.certidao.livro },
                                        { label: 'Folha', value: d.certidao.folha },
                                        { label: 'Cartório', value: titleCase(d.certidao.cartorio), full: true },
                                        { label: 'Município', value: titleCase(d.certidao.municipio) },
                                    ]}
                                />
                            ) : (
                                <NaoInformado>Certidão não informada.</NaoInformado>
                            )}
                        </SectionCard>
                    </div>

                    {d.cnh && (
                        <SectionCard title="Carteira de habilitação (CNH)" icon={Car}>
                            <InfoGrid
                                columns={4}
                                items={[
                                    { label: 'Número', value: d.cnh.numero && <Numero valor={d.cnh.numero} label="CNH" /> },
                                    { label: 'Categoria', value: d.cnh.categoria },
                                    { label: 'Emissão', value: formatarData(d.cnh.emissao) },
                                    { label: 'Vencimento', value: formatarData(d.cnh.vencimento) },
                                    { label: '1ª habilitação', value: formatarData(d.cnh.primeiraHabilitacao) },
                                ]}
                            />
                        </SectionCard>
                    )}
                </>
            )}
        </FichaPage>
    );
}
