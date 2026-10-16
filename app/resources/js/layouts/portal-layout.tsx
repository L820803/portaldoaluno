import { BottomNav } from '@/components/portal/bottom-nav';
import { PortalNotices } from '@/components/portal/notice';
import { PortalHeader } from '@/components/portal/portal-header';
import { PortalSidebar } from '@/components/portal/portal-sidebar';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import * as React from 'react';
import { useState } from 'react';

interface PortalLayoutProps {
    children: React.ReactNode;
}

/**
 * Layout das páginas do aluno: barra lateral (computador), barra inferior (celular),
 * barra superior com matrícula e conta, e avisos.
 */
export default function PortalLayout({ children }: PortalLayoutProps) {
    // Barra lateral expandida/recolhida, lembrada entre as visitas
    const [aberta, setAberta] = useState(() => {
        try {
            return localStorage.getItem('sidebar') !== 'false';
        } catch {
            return true;
        }
    });

    const alternar = (valor: boolean) => {
        setAberta(valor);

        try {
            localStorage.setItem('sidebar', String(valor));
        } catch {
            // Sem armazenamento local (navegação privada): só não lembra
        }
    };

    return (
        <SidebarProvider open={aberta} onOpenChange={alternar}>
            <a
                href="#conteudo"
                className="bg-primary text-primary-foreground sr-only z-50 rounded-md px-4 py-2 focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
            >
                Pular para o conteúdo
            </a>

            <PortalSidebar />

            <SidebarInset className="min-w-0">
                <PortalHeader />

                {/* Conteúdo ocupa toda a largura à direita da barra lateral */}
                <div id="conteudo" className="w-full flex-1 px-4 pt-6 pb-[calc(var(--bottom-nav-height)+1.5rem)] md:px-8 md:pt-8 md:pb-12">
                    <PortalNotices />
                    {children}
                </div>
            </SidebarInset>

            <BottomNav />
        </SidebarProvider>
    );
}
