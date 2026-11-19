/** Tipos do contrato entre backend e páginas (docs/contrato-paginas.md). */
export type HumorFala = 'feliz' | 'bravo' | 'neutro' | 'ajuda';

export type Fala = {
    texto: string;
    humor: HumorFala;
    variacao_id: number | null;
};

export type Zoeira = 'fofo' | 'sarc' | 'sem';
export type Serie = '9ano' | '1em' | '2em' | '3em' | 'cursinho';

export type Preferencias = {
    zoeira: Zoeira;
    zoeira_maxima: 'sarc' | 'sem';
    som: boolean;
    musica: boolean;
    reduzir_movimento: boolean;
    ultima_materia: string | null;
};

export type Aluno = {
    id: number;
    apelido: string;
    serie: Serie;
    idade: number;
    preferencias: Preferencias;
};

export type DadosGabaritu = {
    email_contato: string;
    repositorio_url: string;
    cadastro_aberto: boolean;
};

export type PropsCompartilhadas = {
    auth: { user: Aluno | null };
    gabaritu: DadosGabaritu;
};

export type MateriaResumo = {
    slug: string;
    nome: string;
    area: string;
    nota: number | null;
    assuntos_total: number;
    revisao_hoje: number;
};

export type QuestionarioResumo = {
    id: number;
    titulo: string;
    ordem: number;
    feito: boolean;
    melhor_acertos: number | null;
};

export type AssuntoResumo = {
    slug: string;
    nome: string;
    nota: number | null;
    amostra_baixa: boolean;
    questionarios: QuestionarioResumo[];
};

export type Alternativa = {
    id: number;
    letra: 'A' | 'B' | 'C' | 'D' | 'E';
    texto: string;
};

export type Tentativa = {
    id: number;
    tipo: 'questionario' | 'revisao';
    titulo: string;
    materia: { slug: string; nome: string };
    assunto: { slug: string; nome: string };
    posicao: number;
    total: number;
};

export type QuestaoDados = {
    id: number;
    contexto: string | null;
    enunciado: string;
    alternativas: Alternativa[];
};

export type RespostaProxima = { url: string } | { resultado_url: string };

export type RespostaApi = {
    correta: boolean;
    alternativa_correta_id: number;
    etiqueta: { id: number; nome: string } | null;
    explicacao: string;
    fala: Fala;
    freio_ativo: boolean;
    chute: boolean;
    proxima: RespostaProxima;
};

export type PontoEvolucao = { data: string; nota: number };
export type ItemDiagnostico = {
    etiqueta: string;
    nome: string;
    percentual: number;
};

export type DesempenhoAssunto = {
    slug: string;
    nome: string;
    nota: number | null;
    amostra_baixa: boolean;
    evolucao: PontoEvolucao[];
    diagnostico: ItemDiagnostico[];
};

export type DesempenhoMateria = Omit<DesempenhoAssunto, never> & {
    assuntos: DesempenhoAssunto[];
};
