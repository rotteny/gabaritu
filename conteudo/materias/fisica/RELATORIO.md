# Relatório: Física (`fisica`)

## Assuntos (5)
1. `eletrodinamica`: Eletrodinâmica
2. `cinematica`: Cinemática
3. `termologia`: Termologia e calorimetria
4. `ondulatoria`: Ondulatória e acústica
5. `dinamica-energia`: Dinâmica e energia mecânica

## Totais
- Questões: **100** (5 arquivos x 20), em 4 questionários de 5 por assunto.
- Dificuldade em cada assunto: 6 fáceis (-1), 8 médias (0), 6 difíceis (1). Total da matéria: 30 / 40 / 30.
- Letra correta em cada assunto: A, B, C, D e E com 4 questões cada (total da matéria: 20 de cada).
- Todas as questões têm 5 alternativas, exatamente 1 correta, e cada alternativa errada aponta para uma etiqueta existente.

## Constantes adotadas nos enunciados (sempre declaradas quando necessárias)
g = 10 m/s²; velocidade do som no ar 340 m/s; luz 3 x 10⁸ m/s; calor específico da água 1 cal/g°C; calor latente de fusão do gelo 80 cal/g; vaporização 540 cal/g; 1 cal = 4,2 J (ou 4 J, informado no enunciado).

## Etiquetas (16) e usos em alternativas erradas
`fis-conta` 116, `fis-formula-trocada` 75, `fis-esqueceu-etapa` 41, `fis-unidade` 35, `fis-ignorou-dado` 35, `fis-proporcao-invertida` 29, `fis-parou-no-intermediario` 20, `fis-esqueceu-quadrado` 10, `fis-calor-latente` 7, `fis-sinal-direcao` 7, `fis-escala-temperatura` 6, `fis-massa-peso` 6, `fis-potencia-energia` 4, `fis-serie-paralelo` 3, `fis-media-aritmetica` 3, `fis-frequencia-periodo` 3.

Observação: `fis-conta` e `fis-formula-trocada` concentram muitos usos porque várias alternativas são erros aritméticos ou conceituais genéricos. As etiquetas específicas (série/paralelo, média aritmética, período/frequência) aparecem em poucas questões, mas todas as 16 são usadas.

## Falas
`falas.json` tem as 16 etiquetas, cada uma com 5 falas fofo, 5 sarc, 5 sem e 1 reserva (todas distintas, até 118 caracteres).

## Verificação
- Toda conta foi refeita antes de gravar, inclusive os valores dos distratores (cada alternativa errada vem de um erro reproduzível). As explicações trazem o passo a passo.
- Validação: `python3 -m json.tool` em todos os arquivos, mais checagem estrutural (20 questões por arquivo, 5 por questionário, 1 correta, etiquetas existentes, ids únicos, distribuição de dificuldade e letras).

## Dúvidas factuais
Nenhuma relevante. Pontos de atenção para a revisão do dono:
- `fis-termologia-009`: assume equilíbrio com o gelo totalmente derretido (a conta confirma que a água quente tem calor suficiente).
- `fis-ondulatoria-015`: a fórmula f = n·v/2L foi dada no enunciado.
- Em algumas questões conceituais (`fis-ondulatoria-011`, `-014`, `-017`, `fis-termologia-014`, `fis-dinamica-energia-018`), as alternativas erradas usam a etiqueta genérica `fis-formula-trocada` (confusão de conceito).
