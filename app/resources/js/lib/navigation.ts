import { type NavGroup, type NavItem } from '@/types';
import { CalendarDays, CalendarX2, Clock, GraduationCap, House, IdCard, Info, LibraryBig, School, ScrollText, UserRound } from 'lucide-react';

/**
 * Menu do aluno. Fonte única para a barra lateral, a barra inferior (celular)
 * e os atalhos da página inicial.
 */
export const inicio: NavItem = { title: 'Início', route: 'dashboard', icon: House };

export const navegacao: NavGroup[] = [
    {
        title: 'Vida escolar',
        items: [
            { title: 'Boletim', route: 'boletim', icon: GraduationCap, description: 'Notas e frequência' },
            { title: 'Faltas', route: 'faltas', icon: CalendarX2, description: 'Dias com falta' },
            { title: 'Horários de aula', short: 'Horários', route: 'horarios', icon: Clock, description: 'Grade da semana' },
            { title: 'Calendário escolar', short: 'Calendário', route: 'calendario', icon: CalendarDays, description: 'Feriados e eventos' },
            { title: 'Histórico escolar', short: 'Histórico', route: 'historico', icon: ScrollText, description: 'Anos concluídos' },
        ],
    },
    {
        title: 'Meus dados',
        items: [
            { title: 'Dados pessoais', route: 'meus-dados', icon: UserRound, description: 'Filiação e endereço' },
            { title: 'Documentos', route: 'documentos', icon: IdCard, description: 'CPF, RG e certidão' },
            { title: 'Outras informações', route: 'outras-informacoes', icon: Info, description: 'Responsável e transporte' },
            { title: 'Minha escola', route: 'minha-escola', icon: School, description: 'Endereço e contatos' },
        ],
    },
    {
        title: 'Serviços',
        items: [{ title: 'Biblioteca', route: 'biblioteca', icon: LibraryBig, description: 'Livros e devoluções' }],
    },
];

/** Itens fixos da barra inferior no celular (o último botão abre o menu completo) */
export const navegacaoInferior: NavItem[] = [
    inicio,
    ...['boletim', 'horarios', 'calendario'].map((r) => navegacao.flatMap((g) => g.items).find((i) => i.route === r)!),
];

/** A URL atual (usePage().url) pertence ao item? */
export function ativo(item: NavItem, url: string): boolean {
    const caminho = route(item.route, undefined, false);

    return url === caminho || url.startsWith(`${caminho}/`) || url.startsWith(`${caminho}?`);
}
