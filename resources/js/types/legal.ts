export type SecaoLegal = {
    id: string;
    titulo: string;
    paragrafos?: string[];
    itens?: string[];
    definicoes?: [string, string][];
};
