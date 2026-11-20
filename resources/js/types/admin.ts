export type Paginado<T> = {
    dados: T[];
    pagina: number;
    por_pagina: number;
    total: number;
    ultima_pagina: number;
};

export type RotasAdmin = {
    dashboard: string;
    alunos: string;
    responsaveis: string;
    questoes: string;
    respostas: string;
    reports: string;
    retencao: string;
    pendencias: string;
    saude: string;
    falas: string;
    sair: string;
};

export type AdminCompartilhado = {
    email: string;
    prefixo: string;
    inatividade_minutos: number;
    restantes_recuperacao: number | null;
    rotas: RotasAdmin;
    exportar: {
        metricas: string;
        cadastros: string;
        questoes: string;
        retencao: string;
    };
};

export type Periodo = '30' | '90' | 'tudo';

export type PontoDia = {
    dia: string;
    cadastros: number;
    alunos_ativos: number;
    logins: number;
    respostas: number;
    visitas_landing: number;
};

export type DashboardProps = {
    periodo: Periodo;
    cartoes: {
        alunos: {
            total: number;
            por_status: { ativo: number; aguardando_responsavel: number };
        };
        responsaveis: number;
        cadastros: {
            hoje: number;
            ultimos_7_dias: number;
            ultimos_30_dias: number;
        };
        ativos: { hoje: number; ultimos_7_dias: number };
        respostas_hoje: number;
    };
    funil: {
        etapas: { chave: string; rotulo: string; valor: number }[];
        menores: { cadastrados: number; autorizados: number };
    };
    series: PontoDia[];
};

export type AlunoLinha = {
    id: number;
    apelido: string;
    email_mascarado: string;
    serie: string | null;
    serie_rotulo: string | null;
    idade: number | null;
    status: string;
    cadastro: string;
    ultima_atividade: string | null;
    respostas: number;
    tem_responsavel: boolean;
    url: string;
};

export type Revelar = {
    url: string;
    exige_codigo: boolean;
    email: string | null;
};

export type ResponsavelLinha = {
    id: number;
    email_mascarado: string;
    alunos_vinculados: number;
    convites_pendentes: number;
    cadastro: string;
    url: string;
};

export type DistribuicaoItem = {
    letra: string;
    correta: boolean;
    total: number;
    pct: number | null;
};

export type QuestaoLinha = {
    id: number;
    codigo: string;
    origem: 'autoral' | 'inep';
    materia: string | null;
    assunto: string | null;
    area: string | null;
    status_juridico: 'pendente' | 'liberada' | 'remover' | null;
    respostas: number;
    pct_acerto: number | null;
    tempo_medio_ms: number | null;
    distribuicao: DistribuicaoItem[];
    reports_abertos: number;
    suspeita: boolean;
    motivos_suspeita: string[];
    url: string;
};

export type ResumoAssunto = {
    id: number;
    nome: string;
    total: number;
    autorais: number;
    inep: number;
    respostas: number;
    pct_acerto: number | null;
};

export type StatusReport = 'aberto' | 'resolvido' | 'descartado';

export type ReportItem = {
    id: number;
    apelido: string;
    motivo: string;
    comentario: string | null;
    status: string;
    criado_em: string;
    url: string;
};

export type GrupoReport = {
    questao: {
        id: number;
        codigo: string;
        origem: string;
        materia: string | null;
        assunto: string | null;
        url: string;
    };
    total: number;
    por_motivo: Record<string, number>;
    reports: ReportItem[];
    atualizar_todos: string;
};

export type CelulaRetencao = {
    base: number;
    voltaram: number;
    pct: number | null;
};
export type Coorte = { semana: string; cadastros: number } & Record<
    string,
    unknown
>;

export type FalaRank = {
    id: number;
    texto: string;
    nivel: string;
    chave: string;
    votos: number;
};
export type GrupoFala = { grupo: string; curtidas: number; rejeicoes: number };
