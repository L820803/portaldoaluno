import { Email, EnderecoBloco, Telefone } from '@/components/portal/contato';
import { FichaPage } from '@/components/portal/ficha-page';
import { SectionCard } from '@/components/portal/section-card';
import { useInitials } from '@/hooks/use-initials';
import { titleCase } from '@/lib/format';
import { type Escola, type FichaProps } from '@/types/aluno';
import { Globe, MapPin, PhoneCall, School, UsersRound } from 'lucide-react';

export default function MinhaEscola(props: FichaProps<Escola>) {
    const getInitials = useInitials();

    return (
        <FichaPage
            title="Minha escola"
            description="Endereço, contatos e direção da escola."
            icon={School}
            vazio="Escola não informada no cadastro"
            {...props}
        >
            {(e) => (
                <>
                    <section className="bg-card flex items-center gap-4 rounded-xl border p-5 shadow-xs">
                        <div className="bg-primary text-primary-foreground flex size-12 shrink-0 items-center justify-center rounded-xl">
                            <School className="size-6" />
                        </div>
                        <h2 className="text-lg font-bold tracking-tight text-balance">{titleCase(e.nome)}</h2>
                    </section>

                    <SectionCard title="Endereço" icon={MapPin}>
                        <EnderecoBloco endereco={e.endereco} />
                    </SectionCard>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <SectionCard title="Fale com a escola" icon={PhoneCall}>
                            {e.telefones.length === 0 && !e.email && !e.site ? (
                                <p className="text-muted-foreground text-sm">Nenhum contato cadastrado.</p>
                            ) : (
                                <ul className="space-y-3 text-sm">
                                    {e.telefones.map((t) => (
                                        <li key={t}>
                                            <Telefone valor={t} />
                                        </li>
                                    ))}
                                    {e.email && (
                                        <li>
                                            <Email valor={e.email} />
                                        </li>
                                    )}
                                    {e.site && (
                                        <li>
                                            <a
                                                href={e.site}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-primary inline-flex items-center gap-1.5 font-medium break-all underline-offset-4 hover:underline"
                                            >
                                                <Globe className="size-3.5 shrink-0" />
                                                {e.site.replace(/^https?:\/\//, '')}
                                            </a>
                                        </li>
                                    )}
                                </ul>
                            )}
                        </SectionCard>

                        <SectionCard title="Direção" icon={UsersRound}>
                            {e.diretores.length === 0 ? (
                                <p className="text-muted-foreground text-sm">Direção não informada.</p>
                            ) : (
                                <ul className="space-y-3">
                                    {e.diretores.map((d) => (
                                        <li key={`${d.nome}-${d.turno}`} className="flex items-center gap-3">
                                            <span className="bg-accent text-accent-foreground flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold">
                                                {getInitials(titleCase(d.nome))}
                                            </span>
                                            <div className="min-w-0 text-sm leading-tight">
                                                <p className="truncate font-medium">{titleCase(d.nome)}</p>
                                                {d.turno && (
                                                    <p className="text-muted-foreground text-xs">Turno: {titleCase(d.turno).toLowerCase()}</p>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </SectionCard>
                    </div>
                </>
            )}
        </FichaPage>
    );
}
