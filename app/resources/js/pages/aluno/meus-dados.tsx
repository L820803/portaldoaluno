import { AlunoAvatar } from '@/components/portal/aluno-avatar';
import { EnderecoBloco, Telefone } from '@/components/portal/contato';
import { CopyButton } from '@/components/portal/copy-button';
import { FichaPage } from '@/components/portal/ficha-page';
import { InfoGrid } from '@/components/portal/info-grid';
import { SectionCard } from '@/components/portal/section-card';
import { formatarData, titleCase } from '@/lib/format';
import { type FichaProps, type Identificacao } from '@/types/aluno';
import { Cake, Home, IdCard, Phone, UserRound, Users } from 'lucide-react';

export default function MeusDados(props: FichaProps<Identificacao>) {
    return (
        <FichaPage title="Dados pessoais" description="Identificação, filiação, endereço e contatos." icon={UserRound} {...props}>
            {(d) => (
                <>
                    {/* Perfil */}
                    <section className="bg-card flex flex-col items-center gap-4 rounded-xl border p-6 text-center shadow-xs sm:flex-row sm:text-left">
                        <AlunoAvatar nome={d.nome ?? ''} foto={d.foto} className="ring-accent size-20 text-2xl ring-4" />
                        <div className="min-w-0 flex-1">
                            <h2 className="text-xl font-bold tracking-tight text-balance">{titleCase(d.nome)}</h2>
                            <div className="text-muted-foreground mt-2 flex flex-wrap justify-center gap-x-4 gap-y-1 text-sm sm:justify-start">
                                {d.codigo && (
                                    <span className="inline-flex items-center gap-1">
                                        <IdCard className="size-4" />
                                        Código do aluno <strong className="text-foreground font-semibold">{d.codigo}</strong>
                                        <CopyButton value={d.codigo} label="código do aluno" className="size-6" />
                                    </span>
                                )}
                                {d.nascimento && (
                                    <span className="inline-flex items-center gap-1">
                                        <Cake className="size-4" />
                                        {formatarData(d.nascimento)}
                                        {d.idade !== null && ` · ${d.idade} anos`}
                                    </span>
                                )}
                            </div>
                        </div>
                    </section>

                    <SectionCard title="Identificação" icon={IdCard}>
                        <InfoGrid
                            items={[
                                { label: 'Código INEP', value: d.inep },
                                { label: 'Data de nascimento', value: formatarData(d.nascimento) },
                                { label: 'Sexo', value: titleCase(d.sexo) },
                                { label: 'Cor/raça', value: titleCase(d.raca) },
                                { label: 'Estado civil', value: titleCase(d.estadoCivil) },
                                { label: 'Nacionalidade', value: titleCase(d.nacionalidade) },
                                { label: 'Naturalidade', value: titleCase(d.naturalidade) },
                                { label: 'País', value: titleCase(d.pais) },
                            ]}
                        />
                    </SectionCard>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <SectionCard title="Filiação" icon={Users}>
                            <InfoGrid
                                columns={2}
                                items={[
                                    { label: 'Mãe', value: titleCase(d.filiacao.mae), full: true },
                                    { label: 'Pai', value: titleCase(d.filiacao.pai), full: true },
                                ]}
                            />
                        </SectionCard>

                        <SectionCard title="Contatos" icon={Phone}>
                            <InfoGrid
                                columns={2}
                                items={[
                                    { label: 'Telefone', value: d.contatos.telefone && <Telefone valor={d.contatos.telefone} /> },
                                    { label: 'Celular', value: d.contatos.celular && <Telefone valor={d.contatos.celular} /> },
                                ]}
                            />
                        </SectionCard>
                    </div>

                    <SectionCard title="Endereço" icon={Home}>
                        <EnderecoBloco endereco={d.endereco} extra={d.endereco.zona && `Zona ${titleCase(d.endereco.zona).toLowerCase()}`} />
                    </SectionCard>
                </>
            )}
        </FichaPage>
    );
}
