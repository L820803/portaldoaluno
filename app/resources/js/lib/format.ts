/**
 * Formatação de textos e datas vindos do e-cidade (que envia tudo em MAIÚSCULAS
 * e datas no formato AAAA-MM-DD).
 */

const MINUSCULAS = new Set(['e', 'de', 'da', 'das', 'do', 'dos', 'em', 'na', 'nas', 'no', 'nos', 'para', 'por', 'com']);

// Siglas que devem continuar em maiúsculas
const UFS = [
    'AC',
    'AL',
    'AP',
    'AM',
    'BA',
    'CE',
    'DF',
    'ES',
    'GO',
    'MA',
    'MT',
    'MS',
    'MG',
    'PA',
    'PB',
    'PR',
    'PE',
    'PI',
    'RJ',
    'RN',
    'RS',
    'RO',
    'RR',
    'SC',
    'SP',
    'SE',
    'TO',
];
const SIGLAS = new Set([
    'EMEF',
    'EMEIEF',
    'EEEF',
    'EMEI',
    'CMEI',
    'AEE',
    'EJA',
    'CPF',
    'RG',
    'CNH',
    'NIS',
    'INEP',
    'UF',
    'SESDEC',
    'II',
    'III',
    'IV',
    ...UFS,
]);

/**
 * "MARIA EDUARDA DA SILVA" → "Maria Eduarda da Silva"; "8º ANO A" → "8º Ano A".
 */
export function titleCase(texto: string | null | undefined): string {
    if (!texto) {
        return '';
    }

    const partes = texto.trim().split(/(\s+|-|\/)/);

    return partes
        .map((parte, i) => {
            if (/^(\s+|-|\/)$/.test(parte) || parte === '') {
                return parte;
            }

            const upper = parte.toLocaleUpperCase('pt-BR');

            // Siglas e letra de turma no fim ("8º ANO A")
            if (SIGLAS.has(upper) || (i === partes.length - 1 && i > 0 && parte.length === 1)) {
                return upper;
            }

            const lower = parte.toLocaleLowerCase('pt-BR');

            // Conectivos e o "feira" de "Segunda-feira"
            if (i > 0 && (MINUSCULAS.has(lower) || (lower === 'feira' && partes[i - 1] === '-'))) {
                return lower;
            }

            return lower.charAt(0).toLocaleUpperCase('pt-BR') + lower.slice(1);
        })
        .join('');
}

/**
 * Situações e respostas: "EM ANDAMENTO" → "Em andamento".
 */
export function fraseCase(texto: string | null | undefined): string {
    const lower = (texto ?? '').trim().toLocaleLowerCase('pt-BR');

    return lower.charAt(0).toLocaleUpperCase('pt-BR') + lower.slice(1);
}

/**
 * Primeiro nome para saudações: "MARIA EDUARDA DA SILVA" → "Maria".
 */
export function primeiroNome(nome: string | null | undefined): string {
    return titleCase(nome).split(' ')[0] ?? '';
}

/**
 * Converte "AAAA-MM-DD" em Date local (sem deslocamento de fuso).
 */
export function parseData(valor: string | null | undefined): Date | null {
    if (!valor) {
        return null;
    }

    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(valor);
    const data = m ? new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])) : new Date(valor);

    return Number.isNaN(data.getTime()) ? null : data;
}

/**
 * "2026-03-21" → "21/03/2026" (ou "21 de março de 2026" com estilo "longo").
 */
export function formatarData(valor: string | null | undefined, estilo: 'curto' | 'longo' = 'curto'): string {
    const data = parseData(valor);

    if (!data) {
        return '';
    }

    return estilo === 'longo'
        ? data.toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' })
        : data.toLocaleDateString('pt-BR');
}

/**
 * Saudação conforme a hora do dia.
 */
export function saudacao(agora: Date = new Date()): string {
    const hora = agora.getHours();

    if (hora < 12) {
        return 'Bom dia';
    }

    return hora < 18 ? 'Boa tarde' : 'Boa noite';
}

/**
 * Rótulo da matrícula para o seletor: "2026 · 8º Ano".
 */
export function rotuloMatricula(matricula: { ano: number | null; etapa: string }): string {
    return [matricula.ano, titleCase(matricula.etapa)].filter(Boolean).join(' · ');
}

/**
 * Valor vazio vira travessão, para tabelas e listas de dados.
 */
export function ouTraco(valor: string | number | null | undefined): string {
    if (valor === null || valor === undefined) {
        return '—';
    }

    const texto = String(valor).trim();

    return texto === '' ? '—' : texto;
}

/**
 * "69999991234" → "(69) 99999-1234"; "6932213344" → "(69) 3221-3344".
 */
export function formatarTelefone(valor: string | null | undefined): string {
    const d = (valor ?? '').replace(/\D/g, '');

    if (d.length === 11) {
        return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
    }

    if (d.length === 10) {
        return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
    }

    if (d.length === 8 || d.length === 9) {
        return `${d.slice(0, -4)}-${d.slice(-4)}`;
    }

    return (valor ?? '').trim();
}

/**
 * Link para ligar ("tel:"), ou null quando não há número utilizável.
 */
export function linkTelefone(valor: string | null | undefined): string | null {
    const d = (valor ?? '').replace(/\D/g, '');

    if (d.length < 8) {
        return null;
    }

    return d.length === 10 || d.length === 11 ? `tel:+55${d}` : `tel:${d}`;
}

export interface Endereco {
    logradouro: string | null;
    numero: string | null;
    complemento: string | null;
    bairro: string | null;
    municipio: string | null;
    uf: string | null;
    cep: string | null;
}

/**
 * Endereço em até três linhas legíveis.
 */
export function linhasEndereco(e: Endereco): string[] {
    const rua = [titleCase(e.logradouro), e.numero].filter(Boolean).join(', ');
    const linha1 = [rua, titleCase(e.complemento)].filter(Boolean).join(' - ');
    const cidade = [titleCase(e.municipio), e.uf?.toUpperCase()].filter(Boolean).join('/');
    const linha2 = [titleCase(e.bairro), cidade].filter(Boolean).join(' - ');

    return [linha1, linha2, e.cep ? `CEP ${e.cep}` : ''].filter(Boolean);
}

/**
 * Link de busca no Google Maps para o endereço (null se faltar a rua).
 */
export function linkMapa(e: Endereco): string | null {
    if (!e.logradouro) {
        return null;
    }

    const consulta = [e.logradouro, e.numero, e.bairro, e.municipio, e.uf, e.cep].filter(Boolean).join(', ');

    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(consulta)}`;
}
