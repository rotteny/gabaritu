# Relatório: Espanhol (`espanhol`)

## Assuntos
1. Compreensão geral do texto (`compreensao-geral`)
2. Gêneros textuais e intenção do autor (`generos-e-proposito`)
3. Vocabulário em contexto (`vocabulario-em-contexto`)
4. Heterossemânticos / falsos amigos (`falsos-amigos`)
5. Gramática em contexto (`gramatica-em-contexto`)

## Totais
- 100 questões (5 assuntos x 20), ids únicos, 4 questionários de 5 por assunto.
- Todas com 5 alternativas (A–E), exatamente 1 correta; cada errada com etiqueta de `etiquetas.json` (13 etiquetas, prefixo `esp-`).
- Validação com `python3 -m json.tool`: todos os arquivos válidos.

## Distribuição por assunto
- Dificuldade (igual nos 5 assuntos): 6 fáceis (-1), 8 médias (0), 6 difíceis (1), espalhadas pelos 4 questionários.
- Letra correta (igual nos 5 assuntos): A=4, B=4, C=4, D=4, E=4.

## Etiquetas usadas (vezes como alternativa errada)
`esp-sentido-errado` 91, `esp-extrapolou` 74, `esp-intencao-errada` 38, `esp-ignorou-negacao` 34, `esp-falso-amigo` 25, `esp-tempo-verbal` 25, `esp-detalhe-central` 23, `esp-tom-errado` 23, `esp-conector-errado` 18, `esp-referente-errado` 15, `esp-generalizou` 12, `esp-palavra-solta` 11, `esp-portunhol` 11.

Obs.: `esp-sentido-errado` e `esp-extrapolou` também cobrem distratores inventados em vocabulário. `esp-portunhol` aparece pouco; a ideia de "assumiu que é igual ao português" está em parte também em `esp-falso-amigo`.

## Falas
13 etiquetas x (5 fofo + 5 sarc + 5 sem + 1 reserva), todas diferentes entre si, até ~140 caracteres.

## Dúvidas factuais
Nenhuma questão com dúvida factual aberta. Pontos a conferir com falante nativo ou professor de espanhol:
- `falsos-amigos` 10: "pelo" e "chico" têm mais de um sentido; o contexto da frase sustenta o gabarito.
- `falsos-amigos` 16: "salsa" é molho no contexto de massa; a explicação cita o gênero musical como pegadinha.
- `gramatica-em-contexto` 14: "entrada" como ingresso (uso comum na Espanha e em parte da América Latina).
- `vocabulario-em-contexto` 13: "rico" como gostoso é uso coloquial comum na América Latina.

Textos todos autorais; nenhuma letra de música, nenhuma obra protegida, nenhuma marca ou pessoa real.
