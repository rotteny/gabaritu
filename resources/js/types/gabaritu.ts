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

export type AvatarAluno = import('@/data/avatares').AvatarSlug;

export type StatusAluno = 'ativo' | 'aguardando_responsavel';

export type Aluno = {
    id: number;
    tipo?: 'aluno';
    status?: StatusAluno;
    avatar?: AvatarAluno | null;
    apelido: string;
    serie: Serie;
    idade: number;
    preferencias: Preferencias;
};

/** Usuário logado do tipo responsável (auth.user.tipo = 'responsavel'). */
export type UsuarioResponsavel = {
    id: number;
    tipo: 'responsavel';
    nome: string;
    email?: string;
};

export type DadosGabaritu = {
    email_contato: string;
    repositorio_url: string;
    cadastro_aberto: boolean;
};

export type PropsCompartilhadas = {
    auth: { user: Aluno | UsuarioResponsavel | null };
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

export type ImagemQuestao = {
    url: string;
    alt: string;
    largura: number;
    altura: number;
};

export type Alternativa = {
    id: number;
    letra: 'A' | 'B' | 'C' | 'D' | 'E';
    texto: string;
    imagem: ImagemQuestao | null;
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
    imagem: ImagemQuestao | null;
    fonte: string | null;
    alternativas: Alternativa[];
};

export type RespostaProxima = { url: string } | { resultado_url: string };

export type RespostaApi = {
    correta: boolean;
    alternativa_correta_id: number;
    etiqueta: { id: number; nome: string } | null;
    explicacao: string | null;
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

/* Responsável legal (docs/responsaveis.md) */
export type Parentesco = 'pai' | 'mae' | 'avo' | 'tutor' | 'outro';
export type TipoConvite =
    | 'autorizacao'
    | 'coresponsavel'
    | 'coresponsavel_aluno'
    | 'transferencia';

export type StatusVinculo = 'ativo' | 'suspenso';

export type ResponsavelDoAluno = {
    /** Só vem no painel do responsável; é o id do usuário responsável. */
    id?: number;
    nome: string;
    parentesco: Parentesco;
    parentesco_outro: string | null;
    email_mascarado: string;
    status: StatusVinculo;
    autorizado_em: string;
    sou_eu?: boolean;
};

export type Solicitante = {
    nome: string | null;
    parentesco: Parentesco;
    parentesco_outro: string | null;
    email_mascarado: string;
};

export type PedidoPendente = {
    id: number;
    solicitante: Solicitante;
    aluno_aceitou: boolean;
    expira_em: string;
};

export type PedidoEnviado = {
    id: number;
    email_mascarado: string;
    parentesco: Parentesco;
    parentesco_outro: string | null;
    expira_em: string;
};

export type PedidoDeVinculo = {
    id: number;
    solicitante: Solicitante;
    expira_em: string;
    aguarda_responsavel: boolean;
    aceito_por_mim: boolean;
};

export type ConviteDoCadastro = {
    token: string;
    responsavel: { nome: string | null };
    email: string;
    parentesco: Parentesco;
    parentesco_outro: string | null;
    expira_em: string;
};

export type ConvitePendente = {
    id?: number;
    /** Convite feito pelo aluno: qualquer responsável ativo pode vetar enquanto estiver pendente. */
    pode_cancelar?: boolean;
    email_mascarado: string;
    tipo: TipoConvite;
    expira_em: string;
};

export type NotaResumo = { slug: string; nome: string; nota: number | null };

export type AlunoAcompanhado = {
    id: number;
    avatar?: AvatarAluno | null;
    apelido: string;
    serie: Serie;
    idade: number;
    status: StatusAluno;
    teto_zoeira_responsavel: Zoeira;
    responsaveis: ResponsavelDoAluno[];
    convites_pendentes: ConvitePendente[];
    pedidos_pendentes?: PedidoPendente[];
    /** Resumo por matéria; opcional, fora do contrato original. */
    notas?: NotaResumo[];
};

export type ConviteDados = {
    aluno: { apelido: string; serie: Serie };
    email: string;
    tipo: TipoConvite;
    parentesco: Parentesco;
    parentesco_outro: string | null;
    expira_em: string;
};

export type ResponsavelDoAlunoConta = Omit<
    ResponsavelDoAluno,
    'sou_eu' | 'id'
> & { id: number };
export type ConviteDoAluno = {
    id: number;
    email_mascarado: string;
    parentesco: Parentesco;
    parentesco_outro: string | null;
    expira_em: string;
};
