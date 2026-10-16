import PortalLayout from '@/layouts/portal-layout';
import { type BreadcrumbItem } from '@/types';

interface AppLayoutProps {
    children: React.ReactNode;
    /** Mantido por compatibilidade com as páginas do kit; o portal não exibe breadcrumbs */
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children }: AppLayoutProps) => <PortalLayout>{children}</PortalLayout>;
