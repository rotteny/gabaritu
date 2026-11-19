# Relatório: Matemática (`matematica`)

## Assuntos (5)
1. `razao-proporcao`: Razão, proporção e regra de três
2. `porcentagem`: Porcentagem
3. `estatistica-probabilidade`: Estatística e probabilidade
4. `geometria`: Geometria plana e espacial
5. `funcoes`: Funções afim e quadrática

## Totais
- Questões: **100** (5 arquivos x 20), em 4 questionários de 5 por assunto.
- Dificuldade em cada assunto: 6 fáceis (-1), 8 médias (0), 6 difíceis (1). Total da matéria: 30 / 40 / 30.
- Letra correta em cada assunto: A, B, C, D e E com 4 questões cada (distribuição uniforme; total da matéria: 20 de cada).
- Todas as questões têm 5 alternativas, exatamente 1 correta, e cada alternativa errada aponta para uma etiqueta existente.

## Etiquetas (19) e quantas vezes foram usadas em alternativas erradas
`mat-erro-de-conta` 60, `mat-formula-errada` 56, `mat-parou-no-intermediario` 55, `mat-ignorou-dado` 32, `mat-esqueceu-base` 30, `mat-pegou-parte-errada` 26, `mat-sinal` 26, `mat-somou-em-vez-de-multiplicar` 17, `mat-proporcao-invertida` 15, `mat-unidade` 14, `mat-casos-favoraveis` 12, `mat-coeficientes-trocados` 11, `mat-somou-porcentagens` 10, `mat-vertice-raiz` 10, `mat-confundiu-media-mediana` 9, `mat-escala-quadrado` 7, `mat-ignorou-frequencia` 5, `mat-dividiu-igual` 3, `mat-pontos-percentuais` 2.

As etiquetas `mat-pontos-percentuais` e `mat-dividiu-igual` são pouco usadas (cada uma cabe em poucas situações). Todas as 19 aparecem ao menos uma vez.

## Falas
`falas.json` tem as 19 etiquetas, cada uma com 5 falas fofo, 5 sarc, 5 sem e 1 reserva (todas distintas, até 103 caracteres).

## Verificação
- Toda conta foi refeita antes de gravar (parte aritmética e coerência dos distratores). As explicações trazem o caminho passo a passo.
- Validação: `python3 -m json.tool` em todos os arquivos, mais checagem estrutural (20 questões por arquivo, 5 por questionário, 1 correta, etiquetas existentes, ids únicos, alternativas sem texto repetido, distribuição de dificuldade e letras).

## Dúvidas factuais
Nenhuma. As questões usam apenas conteúdo elementar, sem dado externo. Duas observações de redação:
- Em `mat-estatistica-probabilidade-020`, a dificuldade ficou 0 para fechar a distribuição 6/8/6 do assunto (poderia ser entendida como difícil).
- Em `mat-funcoes-009`, o enunciado pede "estritamente mais barato", o que faz a resposta ser 4 meses (em 3 meses os planos custam igual).
