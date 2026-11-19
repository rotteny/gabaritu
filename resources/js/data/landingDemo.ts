export type Zoeira = 'fofo' | 'sarcastico' | 'semfiltro';

export interface NivelZoeira {
    id: Zoeira;
    nome: string;
    descricao: string;
}

export interface AlternativaDemo {
    letra: string;
    texto: string;
    correta: boolean;
    /** Piada do Peixoto por nível, só nas erradas. Zoa a resposta, nunca a pessoa. */
    piadas?: Record<Zoeira, string>;
}

export const niveisZoeira: NivelZoeira[] = [
    { id: 'fofo', nome: 'Fofo', descricao: 'Carinho com uma pitada de piada.' },
    {
        id: 'sarcastico',
        nome: 'Sarcástico',
        descricao: 'Ironia na medida certa.',
    },
    {
        id: 'semfiltro',
        nome: 'Sem filtro',
        descricao: 'Zoeira pesada. Só a partir de 15 anos.',
    },
];

export const questaoDemo = {
    materia: 'Biologia',
    enunciado:
        'Qual organela da célula produz a maior parte do ATP, a energia que ela usa?',
    explicacao:
        'A mitocôndria faz a respiração celular e produz a maior parte do ATP. O ribossomo monta proteínas, o lisossomo digere e o complexo de Golgi embala e entrega.',
    falaInicial: 'Escolhe uma alternativa. Eu fico aqui, de boa. Por enquanto.',
    falaAcerto: 'Acertou! Eu ia soltar uma piada, mas tô orgulhoso demais.',
};

export const alternativasDemo: AlternativaDemo[] = [
    {
        letra: 'A',
        texto: 'Ribossomo',
        correta: false,
        piadas: {
            fofo: 'Ribossomo trabalha muito, mas é com proteína. Energia é com outro colega!',
            sarcastico:
                'Ribossomo? Ele monta proteína. Pra energia, essa resposta ficou sem bateria.',
            semfiltro:
                'Ribossomo pra energia é a resposta que chegou atrasada, errada e ainda pediu desculpa no grupo.',
        },
    },
    { letra: 'B', texto: 'Mitocôndria', correta: true },
    {
        letra: 'C',
        texto: 'Lisossomo',
        correta: false,
        piadas: {
            fofo: 'Lisossomo cuida da faxina da célula. Boa tentativa, falta só a usina!',
            sarcastico:
                'Lisossomo é o pessoal da limpeza. Essa resposta saiu da vassoura, não da usina.',
            semfiltro:
                'Lisossomo? Essa resposta é igual "vou estudar só um pouquinho": não passa.',
        },
    },
    {
        letra: 'D',
        texto: 'Complexo de Golgi',
        correta: false,
        piadas: {
            fofo: 'O Golgi é o correio da célula: embala e entrega. Energia vem de outro lugar!',
            sarcastico:
                'Golgi é o setor de embalagem. Essa resposta foi entregue no endereço errado.',
            semfiltro:
                'Golgi pra energia? Essa resposta caiu igual Windows em dia de apresentação.',
        },
    },
];
