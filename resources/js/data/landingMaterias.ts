export interface AreaMaterias {
    area: string;
    cor: 'orange' | 'yellow' | 'slime' | 'cyan';
    materias: string[];
}

export const areasMaterias: AreaMaterias[] = [
    { area: 'Matemática', cor: 'orange', materias: ['Matemática'] },
    {
        area: 'Linguagens',
        cor: 'yellow',
        materias: [
            'Português',
            'Literatura',
            'Inglês',
            'Espanhol',
            'Artes',
            'Educação Física',
        ],
    },
    {
        area: 'Humanas',
        cor: 'slime',
        materias: ['História', 'Geografia', 'Filosofia', 'Sociologia'],
    },
    {
        area: 'Natureza',
        cor: 'cyan',
        materias: ['Biologia', 'Química', 'Física'],
    },
];
