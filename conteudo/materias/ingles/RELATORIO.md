# Relatório: Inglês (`ingles`)

## Assuntos
1. Compreensão geral do texto (`compreensao-geral`)
2. Gêneros textuais e intenção do autor (`generos-e-proposito`)
3. Vocabulário em contexto (`vocabulario-em-contexto`)
4. Cognatos e falsos cognatos (`cognatos-e-falsos-cognatos`)
5. Gramática em contexto (`gramatica-em-contexto`)

## Totais
- 100 questões (5 assuntos x 20), ids únicos, 4 questionários de 5 por assunto.
- Todas com 5 alternativas (A–E), exatamente 1 correta; cada errada com etiqueta de `etiquetas.json` (13 etiquetas, prefixo `ing-`).
- Validação com `python3 -m json.tool`: todos os arquivos válidos.

## Distribuição por assunto
- Dificuldade (igual nos 5 assuntos): 6 fáceis (-1), 8 médias (0), 6 difíceis (1), espalhadas pelos 4 questionários.
- Letra correta (igual nos 5 assuntos): A=4, B=4, C=4, D=4, E=4.

## Etiquetas usadas (vezes como alternativa errada)
`ing-extrapolou` 102, `ing-intencao-errada` 42, `ing-sentido-errado` 35, `ing-ignorou-negacao` 28, `ing-palavra-solta` 27, `ing-falso-cognato` 26, `ing-tempo-verbal` 24, `ing-traducao-literal` 23, `ing-detalhe-central` 23, `ing-tom-errado` 22, `ing-conector-errado` 18, `ing-generalizou` 15, `ing-referente-errado` 15.

Obs.: `ing-extrapolou` aparece bastante porque foi usada também como etiqueta de distrator inventado em questões de vocabulário (alternativa sem apoio no contexto). O revisor pode redistribuir se quiser mais precisão.

## Falas
13 etiquetas x (5 fofo + 5 sarc + 5 sem + 1 reserva), todas diferentes entre si, até ~140 caracteres.

## Dúvidas factuais
Nenhuma questão com dúvida factual aberta. Pontos a conferir na revisão do dono:
- `vocabulario-em-contexto` 14: "hit" como "sucesso" (sentido de filme/música).
- `generos-e-proposito` 15: manchete satírica (texto autoral, humor depende do leitor reconhecer a situação).
- `gramatica-em-contexto` 7: condicional com "I'd go ... if I had", conferida como hipótese no presente.

Textos todos autorais; nenhuma letra de música, nenhuma obra protegida, nenhuma marca ou pessoa real.
