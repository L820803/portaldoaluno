import { type Endereco } from '@/lib/format';

/** Props das telas cadastrais (App\Http\Controllers\Portal\FichaController) */
export interface FichaProps<T> {
    dados: T | null;
    erro: boolean;
}

/** FichaAluno::identificacao() */
export interface Identificacao {
    nome: string | null;
    codigo: string | null;
    inep: string | null;
    foto: string | null;
    nascimento: string | null;
    idade: number | null;
    sexo: string | null;
    raca: string | null;
    estadoCivil: string | null;
    nacionalidade: string | null;
    pais: string | null;
    naturalidade: string | null;
    filiacao: { mae: string | null; pai: string | null };
    endereco: Endereco & { zona: string | null };
    contatos: { telefone: string | null; celular: string | null };
}

/** FichaAluno::documentos() */
export interface Documentos {
    cpf: string | null;
    passaporte: string | null;
    identidade: {
        numero: string | null;
        complemento: string | null;
        orgaoEmissor: string | null;
        uf: string | null;
        expedicao: string | null;
    } | null;
    certidao: {
        tipo: string | null;
        matricula: string | null;
        termo: string | null;
        livro: string | null;
        folha: string | null;
        cartorio: string | null;
        municipio: string | null;
        emissao: string | null;
    } | null;
    cnh: {
        numero: string | null;
        categoria: string | null;
        emissao: string | null;
        vencimento: string | null;
        primeiraHabilitacao: string | null;
    } | null;
}

/** FichaAluno::outrasInformacoes() */
export interface OutrasInformacoes {
    responsavel: { nome: string | null; email: string | null; celular: string | null };
    contato: { email: string | null; telefone: string | null };
    programas: { bolsaFamilia: string | null; nis: string | null };
    transporte: { publico: string | null; poderPublico: string | null; meios: string[] };
    procedencia: { local: string | null; data: string | null };
    profissao: string | null;
    escolarizacaoOutroEspaco: string | null;
    observacao: string | null;
}

/** FichaAluno::escola() */
export interface Escola {
    nome: string;
    endereco: Endereco;
    telefones: string[];
    email: string | null;
    site: string | null;
    diretores: { nome: string; turno: string | null }[];
}

/** Erro do e-cidade numa prop adiada do início */
export interface ErroConsulta {
    erro: true;
}

/** PainelAluno::matricula() */
export interface ResumoMatricula {
    codigo: number;
    situacao: string | null;
    resultado: string | null;
    etapa: string | null;
    turma: string | null;
    turno: string | null;
    escola: string | null;
    disciplinas: number;
    frequencia: number | null;
    faltas: number;
    ultimoPeriodo: string | null;
    abaixoDaMedia: { disciplina: string; periodo: string; nota: string }[];
}

/** PainelAluno::aulas() */
export interface AulasDoDia {
    dia: string | null;
    hoje: boolean;
    aulas: { inicio: string | null; fim: string | null; disciplina: string; professor: string | null }[];
}

/** PainelAluno::emprestimos() */
export interface Emprestimo {
    titulo: string | null;
    biblioteca: string | null;
    retirada: string | null;
    devolverAte: string | null;
    atrasado: boolean;
}

export function ehErro(valor: unknown): valor is ErroConsulta {
    return typeof valor === 'object' && valor !== null && 'erro' in valor;
}

/** VidaEscolar::boletim() */
export interface Boletim {
    matricula: {
        codigo: number;
        data: string | null;
        situacao: string | null;
        escola: string | null;
        etapa: string | null;
        turma: string | null;
        turno: string | null;
        calendario: string | null;
        resultado: string | null;
        emAndamento: boolean;
    };
    periodos: { codigo: number; nome: string | null; abreviatura: string | null; formaAvaliacao: string | null }[];
    disciplinas: {
        regencia: number;
        nome: string | null;
        notas: { periodo: number; nota: string | null; faltas: number; atingiuMinimo: boolean; temParecer: boolean }[];
        aulas: number | null;
        frequencia: number | null;
        notaFinal: string | null;
        resultado: string | null;
        resultadoAbreviado: string | null;
        progressaoParcial: boolean;
    }[];
    atividades: {
        escola: string | null;
        turma: string | null;
        tipo: string;
        turno: string | null;
        atividades: string[];
        profissionais: { nome: string | null; horarios: { dia: string | null; inicio: string | null; fim: string | null }[] }[];
    }[];
}

/** VidaEscolar::parecer() (GET /boletim/parecer/{regencia}/{periodo}) */
export interface Parecer {
    disciplina: string | null;
    periodo: string | null;
    nota: string | null;
    parecer: string | null;
    amparo: string | null;
    padronizados: { descricao: string; legenda: string | null }[];
}

/** VidaEscolar::faltas() */
export interface Faltas {
    faltas: { data: string; disciplinas: string[]; total: number }[];
    total: number;
    dias: number;
}

/** VidaEscolar::historico() */
export interface Historico {
    etapas: {
        etapa: string | null;
        ano: number | null;
        curso: string | null;
        escola: string | null;
        resultado: string | null;
        diasLetivos: string | null;
        cargaHoraria: string | null;
        disciplinas: {
            nome: string | null;
            situacao: string | null;
            aproveitamento: string | null;
            resultado: string | null;
            cargaHoraria: string | null;
        }[];
    }[];
}

/** VidaEscolar::horarios() */
export interface Horarios {
    turnos: {
        nome: string | null;
        dias: (string | null)[];
        periodos: {
            nome: string | null;
            inicio: string | null;
            fim: string | null;
            aulas: ({ disciplina: string; professor: string | null } | null)[];
        }[];
    }[];
}

/** VidaEscolar::calendario() */
export interface Calendario {
    ano: number | null;
    inicio: string | null;
    fim: string | null;
    periodos: { nome: string | null; inicio: string | null; fim: string | null }[];
    meses: {
        nome: string | null;
        dias: { data: string; letivo: boolean; periodo: string | null; eventos: { nome: string | null; letivo: boolean }[] }[];
    }[];
}

/** VidaEscolar::biblioteca() */
export interface Biblioteca {
    emprestimos: {
        titulo: string | null;
        biblioteca: string | null;
        retirada: string | null;
        devolverAte: string | null;
        devolvidoEm: string | null;
        situacao: 'devolvido' | 'atrasado' | 'emprestado';
    }[];
}
