import type { SecaoLegal } from '@/types/legal';

export const SECOES_TERMOS: SecaoLegal[] = [
    {
        id: 'servico',
        titulo: 'O que é o Gabaritu',
        paragrafos: [
            'O Gabaritu é um projeto gratuito e de código aberto para estudar para o ENEM: questões por matéria e assunto, nota estimada, revisão dos erros e o Peixoto, um mascote que comenta o seu desempenho.',
            'Para criar uma conta, você precisa ter 13 anos ou mais.',
        ],
    },
    {
        id: 'responsavel',
        titulo: 'Menores de 18 anos e o responsável',
        paragrafos: [
            'Todo aluno com menos de 18 anos só usa o Gabaritu depois que um responsável maior de idade cria a conta dele, declara ser responsável legal e autoriza o acesso.',
            'O motivo é o ECA Digital (Lei 15.211/2025), que exige que a conta de quem é menor esteja ligada à conta de um responsável. O Gabaritu aplica essa regra a todos os menores de 18 anos.',
            'Enquanto a autorização não chega, o aluno só acessa a tela de espera, a página Conta (para apagar a conta) e as páginas públicas. Se a autorização não chegar em 30 dias, a conta é apagada.',
        ],
    },
    {
        id: 'aluno',
        titulo: 'Deveres e direitos do aluno',
        itens: [
            'Informar dados verdadeiros, em especial o ano de nascimento e o e-mail do responsável.',
            'Cuidar da própria senha e não usar a conta de outra pessoa.',
            'Saber quem o acompanha: a página Conta mostra os seus responsáveis e o que eles veem.',
            'Pedir a inclusão de outro responsável pela página Conta.',
            'Escolher o seu avatar, um bicho marinho de uma lista pronta (não há upload de foto).',
            'Com 18 anos ou mais, encerrar o acompanhamento pela página Conta.',
            'Apagar a própria conta quando quiser.',
        ],
    },
    {
        id: 'responsavel-deveres',
        titulo: 'Deveres e direitos do responsável',
        itens: [
            'Ser, de fato, responsável legal pelo aluno (pai, mãe, avó/avô, tutor ou guardião com a guarda) e informar o parentesco com sinceridade.',
            'Ter 18 anos ou mais.',
            'Ver o desempenho do aluno, só para leitura: nota estimada, assuntos e diagnóstico de erros.',
            'Baixar o teto de zoeira do Peixoto para aquele aluno.',
            'Convidar outro responsável, transferir a responsabilidade, revogar a própria autorização ou apagar a conta do aluno.',
        ],
    },
    {
        id: 'conduta',
        titulo: 'Conduta',
        paragrafos: ['Ao usar o Gabaritu, você concorda em não:'],
        itens: [
            'tentar acessar contas, dados ou áreas que não são suas;',
            'sobrecarregar ou tentar derrubar o serviço;',
            'usar o serviço para enganar outras pessoas, por exemplo, se passando por um responsável.',
        ],
    },
    {
        id: 'encerrar',
        titulo: 'Encerrar, transferir ou revogar',
        itens: [
            'O aluno apaga a conta na página Conta, informando a senha.',
            'O aluno com 18 anos ou mais pode encerrar o acompanhamento na página Conta: todos os vínculos acabam, os responsáveis são avisados por e-mail e isso não pode ser restaurado.',
            'O responsável pode revogar a própria autorização. Se o aluno ficar sem nenhum responsável ativo, a conta volta a aguardar autorização. O único responsável não consegue revogar: ele transfere a responsabilidade ou apaga a conta do aluno.',
            'Na transferência, o novo responsável recebe um convite. O vínculo do antigo só termina quando o novo aceita.',
            'O responsável pode apagar a conta do aluno, informando a própria senha.',
            'Apagar a conta apaga também as respostas, notas e histórico. Não dá para desfazer.',
        ],
    },
    {
        id: 'limitacao',
        titulo: 'Sem garantia de aprovação',
        paragrafos: [
            'O Gabaritu é um projeto gratuito, mantido por uma pessoa, e é oferecido como está. As notas são estimativas, não a nota oficial do ENEM, e o uso do site não garante aprovação em prova nem vaga em curso.',
            'Podemos mudar ou tirar do ar partes do serviço. Mudanças nestes Termos ganham uma nova versão, indicada no topo desta página.',
        ],
    },
];
