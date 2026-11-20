import type { SecaoLegal } from '@/types/legal';

export const SECOES_PRIVACIDADE: SecaoLegal[] = [
    {
        id: 'resumo',
        titulo: 'O resumo',
        itens: [
            'O Gabaritu guarda só o que precisa pra funcionar.',
            'Quem vê o seu desempenho: você e o seu responsável, se você tiver menos de 18 anos.',
            'Não pedimos CPF, RG, telefone, endereço, foto nem documento de ninguém.',
            'Você (ou o responsável) apaga a conta e tudo some, quando quiser.',
        ],
    },
    {
        id: 'aluno',
        titulo: 'O que a gente guarda do aluno',
        definicoes: [
            [
                'Apelido',
                'o nome que aparece pra você. Não precisa ser o seu nome de verdade.',
            ],
            [
                'Ano de nascimento',
                'pra checar a idade mínima (13 anos), saber se precisa de responsável e liberar o nível de zoeira certo.',
            ],
            ['Série', 'pra organizar o seu estudo.'],
            ['E-mail', 'pra você entrar e recuperar a senha.'],
            [
                'Suas respostas',
                'o que você marcou, se acertou e quanto tempo levou. É daí que saem a nota estimada e a revisão.',
            ],
            [
                'Suas preferências',
                'nível de zoeira, som, reduzir movimento e a última matéria que você abriu.',
            ],
            [
                'E-mail e parentesco do responsável',
                'só se você tem menos de 18 anos: pra enviar o convite de autorização.',
            ],
        ],
        paragrafos: [
            'A senha é guardada embaralhada (criptografada): nem a gente consegue ler.',
        ],
    },
    {
        id: 'responsavel',
        titulo: 'O que a gente guarda do responsável',
        paragrafos: [
            'Pedimos só o necessário para identificar e responsabilizar quem autoriza. Nada além disto:',
        ],
        definicoes: [
            ['Nome', 'identifica quem autorizou.'],
            [
                'E-mail',
                'serve para entrar, para contatar você e prova que o convite chegou até você.',
            ],
            [
                'Ano de nascimento',
                'confirma que você tem 18 anos ou mais. Não pedimos a data completa.',
            ],
            [
                'Parentesco',
                'registra a relação que você declara ter com o aluno (pai, mãe, avó/avô, tutor ou outro).',
            ],
            [
                'Registro do aceite',
                'data e hora, IP, versão dos Termos e as declarações feitas. Prova a autorização dada.',
            ],
        ],
    },
    {
        id: 'nao',
        titulo: 'O que a gente não guarda',
        paragrafos: [
            'Nome completo do aluno, CPF, RG, telefone, endereço, data de nascimento completa, localização, fotos e documentos, de aluno ou de responsável. O Gabaritu também não tem anúncio e não vende dado de ninguém.',
        ],
    },
    {
        id: 'quem',
        titulo: 'Quem vê o quê',
        itens: [
            'O aluno vê o próprio desempenho.',
            'O responsável vê o desempenho dos alunos que acompanha, só para leitura: nota estimada, assuntos e diagnóstico de erros. Ele também vê o apelido, a série, a idade e quem são os outros responsáveis, com o e-mail parcialmente escondido.',
            'O aluno vê quem são os responsáveis dele (nome, parentesco e e-mail parcialmente escondido).',
            'Outros alunos e professores não veem nada.',
        ],
        paragrafos: [
            'O código do Gabaritu é aberto: qualquer pessoa pode conferir o que ele faz.',
        ],
    },
    {
        id: 'motivos',
        titulo: 'Por que tratamos esses dados',
        paragrafos: [
            'Usamos os dados para entregar o serviço que o aluno pediu, para registrar a autorização do responsável e para manter o site seguro.',
            'A LGPD (Lei 13.709/2018) pede que o tratamento de dados de adolescentes seja feito no melhor interesse deles. É esse o critério por trás das nossas escolhas: guardar pouco, exigir a autorização de um responsável para quem é menor (como pede o ECA Digital, Lei 15.211/2025) e deixar o aluno saber quem o acompanha.',
        ],
    },
    {
        id: 'acesso',
        titulo: 'IP e navegador',
        paragrafos: [
            'Para manter você logado e proteger o site, guardamos o IP e o navegador da sessão, e o servidor registra IP, endereço acessado e navegador nos logs de acesso. No aceite do responsável, o IP também fica guardado como prova.',
        ],
    },
    {
        id: 'cookies',
        titulo: 'Cookies',
        paragrafos: [
            'Usamos só os cookies necessários pra você continuar logado e pra proteger o site. Nada de cookie de anúncio.',
        ],
    },
    {
        id: 'retencao',
        titulo: 'Quanto tempo guardamos',
        itens: [
            'Conta e dados de estudo: até a conta ser apagada.',
            'Depois de apagar, cópias de segurança (backup) somem sozinhas em até 7 dias.',
            'Aluno que fica 30 dias sem a autorização do responsável é apagado, com aviso por e-mail 3 dias antes.',
            'Convites vencidos são apagados.',
            'Conta de responsável sem nenhum aluno por 30 dias é apagada.',
        ],
    },
    {
        id: 'direitos',
        titulo: 'Seus direitos',
        paragrafos: [
            'Pela LGPD, você pode pedir para confirmar se tratamos dados seus, acessar, corrigir e apagar esses dados. O responsável pode fazer esses pedidos em nome do aluno que acompanha. Apagar a conta, pela página Conta (aluno) ou pelo painel do responsável, é o jeito mais rápido; para os outros pedidos, escreva para o contato abaixo.',
        ],
    },
    {
        id: 'idade',
        titulo: 'Idade mínima',
        paragrafos: [
            'O Gabaritu é pra quem tem 13 anos ou mais. Quem tem menos de 18 precisa da autorização de um responsável. O nível de zoeira "sem filtro" só é liberado a partir de 15 anos, e o responsável pode baixar o teto.',
        ],
    },
    { id: 'contato', titulo: 'Ficou com dúvida?' },
];
