# Relatório: Biologia

## Assuntos
1. Ecologia (`ecologia`)
2. Genética (`genetica`)
3. Citologia e Metabolismo (`citologia`)
4. Evolução (`evolucao`)
5. Fisiologia Humana e Saúde (`fisiologia-humana`)

## Total de questões
100 (20 por assunto, 4 questionários de 5 questões em cada).

## Dificuldade
-1 (fácil): 30 no total, 6 por assunto. 0 (média): 40, 8 por assunto. 1 (difícil): 30, 6 por assunto. Em cada assunto a sequência por questionário é (-1,-1,0,0,1), (-1,0,0,1,1), (-1,-1,0,0,1), (-1,0,0,1,1).

## Letra correta
Cada letra (A a E) é a correta exatamente 4 vezes em cada assunto.

## Etiquetas usadas (15, número de alternativas erradas)
bio-confundiu-conceitos 101, bio-senso-comum 42, bio-ignorou-dado 37, bio-generalizou 34, bio-erro-calculo 30, bio-troca-orgao-funcao 22, bio-confundiu-processos 20, bio-confundiu-organela 19, bio-sentido-fluxo 18, bio-inverteu-causa-efeito 17, bio-lamarckismo 16, bio-erro-probabilidade 13, bio-meia-verdade 12, bio-confundiu-agente 10, bio-erro-dominancia 9.

`falas.json` tem as 15 etiquetas, cada uma com 5 falas por nível (fofo, sarc, sem) e 1 reserva.

## Autoexame
- Validação estrutural por script em todos os arquivos de questões: 20 questões, 5 alternativas A a E, 1 correta, etiqueta válida em toda alternativa errada, dificuldades e questionários na distribuição pedida.
- Contas refeitas (probabilidades de cruzamentos, densidade, crescimento populacional, IMC, débito cardíaco, carbono-14, 2^n de bactérias, proporção de besouros resistentes).
- Todas as questões são autorais, sem textos de terceiros.

## Questões com dúvida factual
Nenhuma que exigisse troca. Pontos que merecem uma segunda olhada humana:
- `bio-evolucao-012`: o chimpanzé é dado como parente vivo mais próximo (junto do bonobo); o gorila aparece como distrator por ser também um parente próximo, mas mais distante.
- `bio-genetica-014`: a polidactilia é tratada como autossômica dominante "em certas famílias", o que é uma simplificação declarada no enunciado.
- `bio-fisiologia-humana-015`: tema de saúde sexual tratado de forma neutra e informativa; avaliar se o tom serve ao público do app.
- `bio-citologia-004`: a explicação afirma que o O do CO2 acaba na glicose e na água formada, o que é correto, mas é um detalhe além do que a questão pede.
