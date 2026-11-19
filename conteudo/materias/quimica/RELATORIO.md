# Relatório: Química

## Assuntos
1. Estequiometria e Cálculos Químicos (`estequiometria`)
2. Soluções, Concentração e pH (`solucoes-ph`)
3. Química Orgânica (`quimica-organica`)
4. Termoquímica (`termoquimica`)
5. Eletroquímica (`eletroquimica`)

## Total de questões
100 (20 por assunto, 4 questionários de 5 questões em cada).

## Dificuldade
-1 (fácil): 30 no total, 6 por assunto. 0 (média): 40, 8 por assunto. 1 (difícil): 30, 6 por assunto. Em cada assunto a sequência por questionário é (-1,-1,0,0,1), (-1,0,0,1,1), (-1,-1,0,0,1), (-1,0,0,1,1).

## Letra correta
Cada letra (A a E) é a correta exatamente 4 vezes em cada assunto.

## Etiquetas usadas (13, número de alternativas erradas)
qui-senso-comum 75, qui-erro-conta 64, qui-ignorou-dado 60, qui-confundiu-funcao-organica 42, qui-erro-oxirreducao 31, qui-inverteu-razao 25, qui-confundiu-unidade 21, qui-erro-sinal-energia 21, qui-esqueceu-balancear 19, qui-erro-ph 19, qui-erro-massa-molar 12, qui-erro-diluicao 6, qui-ignorou-limitante-rendimento 5.

`falas.json` tem as 13 etiquetas, cada uma com 5 falas por nível (fofo, sarc, sem) e 1 reserva.

## Autoexame
- Validação estrutural por script em todos os arquivos de questões: 20 questões, 5 alternativas A a E, 1 correta, etiqueta válida em toda alternativa errada, dificuldades e questionários na distribuição pedida.
- Todo cálculo foi refeito antes de gravar, com as massas atômicas, constantes e potenciais fornecidos no próprio enunciado (estequiometria, rendimento e pureza, reagente limitante, concentração, diluição, pH e pOH, Lei de Hess, energia de ligação, calorimetria, ddp de pilha e Lei de Faraday).
- Os dados numéricos de entalpia e de potenciais de redução são valores aproximados de tabela e vêm declarados nos enunciados; as questões usam só esses valores.
- Todas as questões são autorais.

## Questões com dúvida factual
Nenhuma que exigisse troca. Pontos para uma segunda olhada humana:
- `qui-termoquimica-006`: as energias por grama (etanol 30, gasolina 46, metano 55, carvão mineral 27 kJ/g) são aproximações didáticas; a ordem entre elas é correta.
- `qui-eletroquimica-005` e `qui-eletroquimica-019`: dependem de potenciais padrão em condições-padrão (25 °C, 1 mol/L), declaradas como valores dados.
- `qui-solucoes-ph-005`, `-019` e `-020`: usam pH + pOH = 14 e Kw = 10⁻¹⁴, válidos a 25 °C (informado nos enunciados).
