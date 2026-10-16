import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    /** Nome da rota (Ziggy) */
    route: string;
    icon: LucideIcon;
    /** Rótulo curto para a barra inferior do celular */
    short?: string;
    /** Frase curta para os atalhos da página inicial */
    description?: string;
}

/** Identidade do órgão (config/portal.php + DadosPrefeitura do e-cidade) */
export interface Portal {
    prefeitura: string;
    orgao: string;
    logo: string | null;
}

export interface Matricula {
    codigo: number;
    ano: number | null;
    etapa: string;
}

/** Resumo do aluno logado (App\Services\Portal\AlunoAtual::resumo) */
export interface AlunoResumo {
    codigo: string;
    nome: string;
    foto: string | null;
    matriculas: Matricula[];
    matriculaAtiva: number | null;
    /** true quando o e-cidade não respondeu */
    indisponivel: boolean;
}

export interface Flash {
    success: string | null;
    error: string | null;
    info: string | null;
}

export interface SharedData {
    name: string;
    auth: Auth;
    portal: Portal;
    aluno: AlunoResumo | null;
    flash: Flash;
    [key: string]: unknown;
}

/** Tabela legada portal.users */
export interface User {
    id: number;
    name: string;
    login: string;
    email: string | null;
    validated_email: boolean;
    avatar?: string;
    [key: string]: unknown;
}
