/** URLs do contrato. Texto puro: as rotas do backend ainda não geram wayfinder. */
export const rotas = {
    inicio: '/inicio',
    desempenho: '/desempenho',
    conta: '/conta',
    privacidade: '/privacidade',
    quemSomos: '/quem-somos',
    revisao: '/revisao',
    entrar: '/entrar',
    cadastro: '/cadastro',
    esqueciSenha: '/esqueci-senha',
    redefinirSenha: '/redefinir-senha',
    landing: '/',
    materia: (slug: string) => `/materias/${slug}`,
    novaTentativa: (questionarioId: number) =>
        `/questionarios/${questionarioId}/tentativas`,
    respostas: (tentativaId: number) => `/tentativas/${tentativaId}/respostas`,
    revisarErradas: (tentativaId: number) =>
        `/tentativas/${tentativaId}/revisao`,
};
