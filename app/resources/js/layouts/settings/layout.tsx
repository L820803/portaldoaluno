import { PageHeader } from '@/components/portal/page-header';
import { SectionCard } from '@/components/portal/section-card';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { KeyRound, Palette, UserCog } from 'lucide-react';

const abas = [
    { title: 'Perfil', route: 'profile.edit', icon: UserCog },
    { title: 'Senha', route: 'password.edit', icon: KeyRound },
    { title: 'Aparência', route: 'appearance', icon: Palette },
];

export default function SettingsLayout({ children }: { children: React.ReactNode }) {
    const { url } = usePage();

    return (
        <>
            <PageHeader title="Minha conta" description="Seu e-mail de contato, sua senha e a aparência do portal." icon={UserCog} />

            <nav aria-label="Seções da conta" className="-mx-4 mb-6 overflow-x-auto px-4 md:mx-0 md:px-0">
                <div className="bg-muted inline-flex gap-1 rounded-xl p-1">
                    {abas.map((aba) => {
                        const atual = url.startsWith(route(aba.route, undefined, false));

                        return (
                            <Link
                                key={aba.route}
                                href={route(aba.route)}
                                prefetch
                                aria-current={atual ? 'page' : undefined}
                                className={cn(
                                    'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                                    atual ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <aba.icon className="size-4" />
                                {aba.title}
                            </Link>
                        );
                    })}
                </div>
            </nav>

            <SectionCard>
                <div className="space-y-10">{children}</div>
            </SectionCard>
        </>
    );
}
