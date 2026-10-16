import { PortalBrand } from '@/components/portal/portal-brand';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { ativo, inicio, navegacao } from '@/lib/navigation';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

function Item({ item, url }: { item: NavItem; url: string }) {
    const { setOpenMobile } = useSidebar();

    return (
        <SidebarMenuItem>
            <SidebarMenuButton asChild isActive={ativo(item, url)} tooltip={item.title} className="h-9 rounded-lg data-[active=true]:font-semibold">
                <Link href={route(item.route)} prefetch onClick={() => setOpenMobile(false)}>
                    <item.icon />
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

/**
 * Menu principal do aluno.
 */
export function PortalSidebar() {
    const { url } = usePage();

    return (
        <Sidebar collapsible="icon" variant="sidebar" className="border-sidebar-border">
            <SidebarHeader className="h-16 justify-center border-b px-3 group-data-[collapsible=icon]:px-2">
                <Link href={route('dashboard')} className="rounded-lg outline-hidden focus-visible:ring-2" aria-label="Início">
                    <PortalBrand className="group-data-[collapsible=icon]:[&>div:last-child]:hidden" />
                </Link>
            </SidebarHeader>

            <SidebarContent className="gap-1 py-3">
                <SidebarGroup className="py-0">
                    <SidebarMenu>
                        <Item item={inicio} url={url} />
                    </SidebarMenu>
                </SidebarGroup>

                {navegacao.map((grupo) => (
                    <SidebarGroup key={grupo.title} className="py-1">
                        <SidebarGroupLabel className="text-muted-foreground/80 text-[11px] font-semibold tracking-wider uppercase">
                            {grupo.title}
                        </SidebarGroupLabel>
                        <SidebarMenu>
                            {grupo.items.map((item) => (
                                <Item key={item.route} item={item} url={url} />
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter className="border-t p-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild tooltip="Sair" className="text-muted-foreground h-9 rounded-lg">
                            <Link href={route('logout')} method="post" as="button">
                                <LogOut />
                                <span>Sair</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>
    );
}
