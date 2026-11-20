# Fontes de questões reais do ENEM para o Gabaritu: relatório de decisão

Data de acesso de todos os links: 19/11/2026. Nada foi baixado em massa. Amostras de inspeção em `/tmp/claude-1000/-home-rotteny/e6b93a06-b4bc-4974-901a-4f6c04b47574/scratchpad/enem-fontes/` (`q2022.json` com 2 questões; `amostra-2022.json` com o ENEM 2022 inteiro, 183 questões, só para medir a proporção de imagem; `lei.txt` com o texto da Lei 9.610).

Este relatório não é parecer jurídico. Onde está escrito VERIFICAR COM ADVOGADO, a resposta não é clara e não foi inventada.

## 1. Fontes

| Fonte | Anos / quantidade | Formato | Separa área/matéria | Gabarito | Marca imagem | Licença declarada | Ativa? |
|---|---|---|---|---|---|---|---|
| [INEP, Provas e gabaritos](https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/enem/provas-e-gabaritos) | abas de 1998 a 2026; ~180 itens/ano | PDF (caderno e gabarito por cor e dia); não consegui listar os arquivos, a página carrega por JS | por dia/caderno, não por matéria | sim (PDF separado) | não, é preciso ler o PDF | rodapé do site: CC BY-ND 3.0 (ver seção 2); sem aviso específico para as provas | sim (atualizada 18/11/2026) |
| [INEP, Microdados](https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados/enem) | 1998 a 2025 | ZIP por ano (CSV). Não traz o texto das questões. Traz o arquivo de itens (`ITENS_PROVA`, espelhado em [Zenodo, 2009-2023](https://zenodo.org/records/18801899)) | área sim; habilidade (H1..H30) por item, a confirmar no dicionário | gabarito do item, a confirmar | não | nenhuma visível na página | sim (2025 atualizado 01/09/2026) |
| [enem.dev](https://api.enem.dev/v1/exams) / [yunger7/enem-api](https://github.com/yunger7/enem-api) | 15 provas, 2009 a 2023; 179 (2009), 183 (2022), 183 (2023); ~2.700 no total | API REST JSON; `docs.enem.dev` | sim: `discipline` (4 áreas) mais `language` (inglês/espanhol); sem matéria | sim (`correctAlternative`, `isCorrect`) | sim, indireto: `files[]`, `file` na alternativa e `![](...)` no `context` | repo GPL-2.0 (vale para o código; os dados não têm licença declarada) | último push 14/12/2025; 366 estrelas; 7 issues abertas; API respondeu hoje. Sem 2024 e 2025 |
| [maritaca-ai/enem](https://huggingface.co/datasets/maritaca-ai/enem/blob/main/README.md) | 2022, 2023, 2024; ~180 por ano (cartão diz "<1K") | JSONL: `question`, `alternatives`, `label`, `figures`, `description` | não | sim (`label`) | sim: `figures` mais `description` (descrição textual das imagens, tirada do caderno laranja de acessibilidade) | apache-2.0 na metadata, sem nota de direito autoral | última alteração 03/12/2024 |
| [eduagarcia/enem_challenge](https://huggingface.co/datasets/eduagarcia/enem_challenge) | 2009 a 2017; 1.432 linhas (o paper original fala em 1.754, então provável recorte sem imagem; não confirmado) | Parquet: `question`, `choices`, `answerKey`, `nullified`, `exam_year` | não | sim; marca anuladas | não | nenhuma (README vazio) | parado desde 30/01/2024 |
| [piresramon/gpt-4-enem](https://github.com/piresramon/gpt-4-enem) (paper [arXiv 2311.14169](https://arxiv.org/html/2311.14169v1), CC BY 4) | 358 questões (paper); anos não confirmados | repo GitHub | não | sim | sim (figuras e legendas) | MIT segundo indexador de terceiros; não conferi no GitHub (retornou 401) | não verificado |
| Outros no Hugging Face (não inspecionados, licença desconhecida): `AndreMitri/ENEM_multimodal_2021_to_2022`, `letmarchezi/ENEM_Brazil_National_High_School_Exam_2022_to_2024`, `Andre13Filho/enem_tests_editions` (jun/2025), `orion-research/ENEM-2022`, `dominguesm/canarim-enem2022-tests` | 2021 a 2024 | vários | ? | ? | ? | ? | não usar sem abrir o cartão de cada um |

Observações:
- Todos os datasets abertos são cópias do mesmo material do INEP. Uma licença Apache ou MIT posta pelo repositório não cria direito que o repositório não tinha: quem copiou não pode relicenciar o que não é dele. Veja a seção 2.
- Parâmetros TRI: o INEP chamou de inédita a divulgação dos parâmetros a, b, c só do ENEM 2020 (planilha, fev/2022, [matéria](https://vestibulares.estrategia.com/portal/noticias/inep-parametros-tri-enem/)). Antes, só por pedido ao Sedap em ambiente controlado e sem cópia ([decisão CMRI](https://www.gov.br/casacivil/pt-br/assuntos/colegiados/comissao-mista-de-reavaliacao-de-informacoes-cmri/decisoes-de-recurso-de-4a-instancia/2022/decisao-115-2022-nup-23546-021242-2022-99.pdf)); o INEP trata os parâmetros como informação classificada (reuso de itens por 5 anos). Para os demais anos, não confirmei publicação. Conclusão: TRI de verdade só para 2020.
- Alternativa para dificuldade: os microdados trazem as respostas dos participantes (a confirmar no dicionário de cada ano). Dá para calcular o percentual de acerto por item e mapear em -1/0/1. Isso é derivado nosso, não é TRI.

## 2. Direito autoral

O que a lei diz (texto conferido em [Lei 9.610/98, PDF da ABMES](https://abmes.org.br/public/arquivos/legislacoes/Lei9610.pdf)):
- Art. 8º, IV: não são protegidos "os textos de tratados ou convenções, leis, decretos, regulamentos, decisões judiciais e demais atos oficiais". A lei não cita prova, exame ou questão. Dizer que uma questão do ENEM é "ato oficial" é interpretação, não texto de lei. VERIFICAR COM ADVOGADO.
- Art. 46, III: não é ofensa a "citação ... de passagens de qualquer obra, para fins de estudo, crítica ou polêmica, na medida justificada para o fim a atingir, indicando-se o nome do autor e a origem da obra". É citação de passagem, não cópia de um acervo inteiro. Reproduzir centenas de questões completas num repo público dificilmente cabe em "na medida justificada". Art. 46, VIII: só "pequenos trechos", e obra integral apenas de artes plásticas, quando a reprodução não for o objetivo principal. Não cobre um banco de questões.
- Art. 47: paráfrase e paródia são livres se não forem "verdadeiras reproduções". Uma questão reescrita do zero, só com a ideia, é terreno seguro (art. 8º, I: ideias e conceitos não são protegidos). É o que já fazemos.

O que o INEP diz:
- O rodapé do portal do INEP diz que "todo o conteúdo deste site está publicado sob" CC BY-ND 3.0 Brasil. ND significa "sem derivações". Se isso valesse para as provas, a licença permitiria redistribuir sem alterar e com crédito, mas proibiria o Gabaritu de adaptar (acrescentar etiquetas, explicação, reescrever) e proibiria relicenciar sob CC BY-SA. Não achei aviso específico de direitos nas provas; não sei se o rodapé cobre os PDFs, nem se o INEP detém todos os direitos das questões (itens podem vir de elaboradores contratados). VERIFICAR COM ADVOGADO, de preferência perguntando ao próprio INEP pelo Fale Conosco, e guardar a resposta.
- Nenhuma das buscas retornou termo de uso do INEP sobre redistribuir as provas com fins educacionais.

Textos de terceiros dentro das questões: mudam a resposta para pior. Trecho literário de autor vivo ou morto há menos de 70 anos, letra de música, tirinha, charge, foto, gráfico de jornal e mapa têm donos próprios. O INEP pode ter autorização restrita ao exame, e ela não se transmite a nós. A defesa do art. 46, III cobre o INEP citando para a prova, não um terceiro que reempacota. Questões com texto de domínio público (autor morto há mais de 70 anos) ou texto institucional/legal (leis, art. 8º, IV) são o menor risco. Não dá para filtrar isso por máquina com segurança.

Compatibilidade com CC BY-SA: não é seguro. Mesmo sem ND, o conteúdo do ENEM não é nosso para licenciar: "CC BY-SA 4.0" no repo diria que qualquer um pode copiar e comercializar, o que o INEP e os autores dos textos não nos autorizaram. Então: (a) não misturar no repo público nem no mesmo `LICENSE`; (b) guardar fora do repo e importar só no servidor tira o risco de redistribuição por `git clone`, mas não resolve a exibição ao público no app, que continua sendo reprodução e comunicação ao público; só reduz a exposição. Para o usuário final, o risco prático é de notificação/retirada, não de processo, em uso sem fim comercial, mas isso é avaliação minha, não jurídica. VERIFICAR COM ADVOGADO.

## 3. Viabilidade técnica (imagem)

Medido no ENEM 2022 inteiro via enem.dev (183 questões; "sem imagem" = `files` vazio, alternativas sem arquivo e sem `![` no contexto):

| Área | Total | Só texto | % |
|---|---|---|---|
| Linguagens | 43 | 34 | 79% |
| Ciências Humanas | 51 | 40 | 78% |
| Ciências da Natureza | 44 | 19 | 43% |
| Matemática | 45 | 17 | 38% |
| Total | 183 | 110 | 60% |

Extrapolando para 15 anos (um ano só como amostra; os anos variam): ~2.700 questões no total e ~1.600 só texto. Descontar anuladas, itens de inglês/espanhol mal separados (as duas línguas contam) e questões que citam "a figura/o texto acima" sem o conteúdo. Cuidado: texto-só não quer dizer livre de terceiros (um poema vem como texto). Matemática e Natureza perdem mais de metade, justamente as áreas em que gráfico e figura são essenciais.

Imagem: pular agora. Suportar imagem depois exige campo `imagem` no esquema, armazenamento e acessibilidade (alt text); e as imagens são justamente a parte de maior risco autoral (foto, tirinha, charge). O `maritaca-ai/enem` traz `description` (texto do caderno acessível) e permite converter parte das questões com imagem em questão só texto de 2022 a 2024; vale avaliar, com o mesmo cuidado jurídico.

## 4. O que falta gerar

| Item | Origem | Como gerar |
|---|---|---|
| Etiqueta por alternativa errada | nenhuma fonte tem | LLM escolhendo do `etiquetas.json` da matéria, mais revisão humana por amostragem (o dono revisa as autorais hoje) |
| Explicação | `eduagarcia` tem o campo, quase todo nulo; resto não tem | LLM, 2 a 4 frases, com conferência de conta em Mat/Fís/Quí |
| Matéria e assunto no nosso esquema | só a área do ENEM; microdados dão a habilidade (H) por item, que ajuda | LLM classifica em uma das 14 matérias e 5 assuntos; descartar o que não cabe nos 5 assuntos; as áreas misturam matérias, então classificar uma a uma |
| Dificuldade (-1, 0, 1) | TRI só de 2020 (publicado); demais anos não confirmados | (a) TRI de 2020 quando houver; (b) percentual de acerto por item calculado dos microdados; (c) LLM como último recurso |
| Fonte e licença do registro | campo `fonte` já existe (`autoral-ia`) | `enem-<ano>-<caderno>-<n>` mais `licenca` própria |

Cada item que passa pelo LLM é derivação nossa de obra do INEP: a seção 2 vale para esse acréscimo (por causa de ND).

## 5. Deduplicação

Regra do dono: nunca 2 questões iguais ou quase iguais no mesmo questionário, nem no acervo.

Chave canônica: `norm(enunciado + alternativas ordenadas)`: minúsculas, sem acento, sem pontuação, espaços colapsados, sem numeração ("Questão 12"), sem "A)", "B)"; markdown de imagem removido; números e unidades preservados. Embaralhar a ordem das alternativas não pode mudar a chave (ordenar antes de hash). Guardar `hash_exato = sha256(chave)` e `hash_alternativas` (só as alternativas).

Camadas, em ordem:
1. Hash exato: derruba a mesma questão vinda de fontes diferentes (enem.dev, maritaca, eduagarcia, INEP) e reaplicação idêntica. Índice único no banco.
2. Similaridade lexical: `pg_trgm` (`similarity()` e índice GIN no texto normalizado). Limiares iniciais: `>= 0.90` rejeita automático; `0.70 a 0.90` vai para revisão. Duas condições cruzadas: similaridade do enunciado E das alternativas.
3. Embeddings (pgvector, que o projeto já usa no gorila-eco): pega paráfrase (questão autoral nossa que reescreve uma do ENEM). Cosseno `>= 0.92` rejeita; `0.85 a 0.92` revisão. Os limiares são palpites: calibrar com um conjunto rotulado de pares (duplicata / não duplicata) do próprio acervo, antes de ligar o bloqueio automático.
4. Questões numéricas (Matemática, Física, Química): mesmo texto e números diferentes não é duplicata para efeito de aprendizado, mas quase igual para o dono. Tratar como revisão, nunca como rejeição automática; o trigram sozinho erra aqui.

Casos:
- Mesma questão de fontes diferentes: camadas 1 e 2; manter 1 registro com lista `fontes[]`.
- ENEM igual a autoral nossa: camadas 2 e 3 contra o acervo autoral. Se achar, manter a autoral e descartar a do ENEM (evita o risco da seção 2).
- Reaplicação e PPL: tratar como o mesmo ano de prova; o hash da camada 1 resolve a repetição de itens; guardar `origem` (ano, aplicação, caderno) para mostrar de onde veio. Ressalva: o enem.dev lista por ano e não deixei claro se inclui PPL/reaplicação (não verificado).
- Dentro do mesmo questionário: regra extra, mais dura (limiar 0.70 em trigramas, qualquer camada), e também por `assunto`. Verificar ao montar cada questionário, não só no importador.

Onde rodar: no importador (script único, idempotente), com teste automático: (a) fixture de pares duplicados conhecidos (reordenação de alternativas, troca de caixa/acento, "Questão 5" vs "5)", paráfrase) que devem ser bloqueados; (b) pares diferentes de mesmo assunto, que devem passar; (c) teste de idempotência (rodar o import 2 vezes não cria nada). Mais uma restrição de unicidade no banco (hash) como rede de segurança, e um comando de auditoria que varre o acervo inteiro e lista pares acima do limiar. Rodar também no acervo atual de 1.400 autorais, para limpar o que já existe.

## Recomendação

1. Nada do ENEM no repo `rotteny/gabaritu` por enquanto. Fonte preferida, se a parte jurídica liberar: INEP (PDFs/microdados) como origem primária; `enem.dev` como conveniência de formato (já traz área, gabarito e marca de imagem); `maritaca-ai/enem` para 2024 e para as descrições de imagem. Não usar `eduagarcia` (sem licença e parado).
2. Volume aproveitável: ~1.600 questões só texto (estimativa), reduzidas por anuladas, itens sem encaixe nos 14 x 5 assuntos e dedup. Estimar 1.000 a 1.400 utilizáveis. Número a confirmar com o piloto.
3. Licença/pasta: se liberado, repositório e pasta separados (por ex. `gabaritu-enem`), com `LICENSE` próprio dizendo que as questões são do INEP e dos autores dos textos, atribuição e termos do INEP, fora do CC BY-SA. O conteúdo autoral permanece em CC BY-SA 4.0. Sem opinião jurídica favorável, usar o ENEM só como referência (frequência de assunto, nível de dificuldade, habilidades) para melhorar as questões autorais, que é o que já é seguro (art. 8º, I e art. 47).

## Próximos passos

1. Dono: escrever ao INEP (Fale Conosco) perguntando se as provas podem ser reproduzidas e redistribuídas com fim educacional sem fim lucrativo, e consultar advogado (art. 8º, IV; art. 46, III e VIII; CC BY-ND do rodapé; textos de terceiros). Guardar as respostas.
2. Enquanto isso, não importar nada. `SHIKAMARU` pode preparar o spike técnico, sem dados reais: esquema com `origem`, `fontes[]`, `hash`, e o importador com teste de dedup.
3. Rodar a dedup sobre as 1.400 autorais atuais (custo baixo, valor imediato).
4. Se o jurídico liberar: piloto com 1 área e 1 ano (por ex. ENEM 2022, Ciências Humanas, 40 questões só texto), medir o custo de etiqueta/explicação/classificação e a taxa de descarte, e só então decidir pela escala.
5. Decidir depois se vale suportar imagem (campo, armazenamento, alt text), só se o piloto mostrar que a falta de Matemática e Natureza pesa.
