# Gabaritu: regras de nota, revisão espaçada e freio (etapa 1)

Documento vivo. Serve de base para o schema (etapa 2) e para os testes do `LEVI`. Itens marcados **PROPOSTA** dependem de aprovação do dono. Todas as constantes ficam em `config/gabaritu.php` (nada de número solto no código). Sugestão de código: classes puras, sem acesso a banco (`NotaCalculator`, `RevisaoEspacada`, `Freio`, `Diagnostico`), para testar sem I/O.

## Constantes

| Constante                                 | Valor                         | Uso                                                                             |
| ----------------------------------------- | ----------------------------- | ------------------------------------------------------------------------------- |
| `DIFICULDADE_B`                           | 1 = -1,0 / 2 = 0,0 / 3 = +1,0 | dificuldade da questão (fácil/média/difícil), em logits                         |
| `THETA_INICIAL`                           | 0,0                           | habilidade inicial por assunto                                                  |
| `THETA_MIN` / `THETA_MAX`                 | -2,0 / +4,0                   | trava (nota 300 a 900)                                                          |
| `K_INICIAL` / `K_NORMAL`                  | 0,50 / 0,25                   | passo da atualização                                                            |
| `K_INICIAL_ATE`                           | 10                            | K inicial vale enquanto `n_primeiras` < 10 (da 11ª em diante, K normal)         |
| `PESO_REPETIDA`                           | 0,25                          | multiplica o K em resposta repetida                                             |
| `MIN_RESPOSTAS_NOTA`                      | 6                             | primeiras respostas para exibir nota                                            |
| `AMOSTRA_BOA`                             | 15                            | abaixo disso, nota com selo "pouca amostra"                                     |
| `CHUTE_MS_MIN`                            | 3000                          | piso do limite de chute (questão curta)                                         |
| `CHUTE_MS_MAX`                            | 8000                          | teto do limite de chute (questão longa)                                         |
| `CHUTE_MS_POR_CARACTERE`                  | 20                            | ms por caractere do enunciado + alternativas (leitura rápida, ~50 caracteres/s) |
| `DIAG_JANELA`                             | 50                            | respostas válidas consideradas                                                  |
| `DIAG_MIN_ERROS` / `DIAG_MIN_OCORRENCIAS` | 5 / 3                         | mínimos do diagnóstico                                                          |
| `CAIXAS_DIAS`                             | [1, 4, 14]                    | intervalo de cada caixa                                                         |
| `REVISAO_MAX_DIA`                         | 10                            | teto da revisão do dia                                                          |
| `FREIO_ERROS`                             | 3                             | aprovado pelo dono (19/11)                                                      |
| `FREIO_SAIDA_ACERTOS`                     | 2                             | aprovado pelo dono (19/11)                                                      |
| `SESSAO_INATIVA_MIN`                      | 30                            | sessão acaba após 30 min sem responder, ou no logout                            |

Fuso fixo para "dia": `America/Sao_Paulo`.

## 1. Nota por assunto (escala ENEM estimada, estilo Elo/Rasch)

Cada aluno tem um `theta` por assunto (começa em 0). A cada resposta que conta:

```
b      = DIFICULDADE_B[questao.dificuldade]
P      = 1 / (1 + e^-(theta - b))          # chance esperada de acertar
K      = (n_primeiras < 10 ? 0,50 : 0,25) * peso
theta' = clamp(theta + K * (resultado - P), -2, +4)    # resultado: 1 acertou, 0 errou
nota   = round(500 + 100 * theta')          # 300..900
exibida = round(nota / 10) * 10             # arredonda de 10 em 10
```

- `peso` = 1 na **primeira** resposta do aluno àquela questão; 0,25 em qualquer repetição (refazer questionário, revisão). Repetida mexe pouco (a memória não vale como habilidade), mas mexe, para o aluno ver evolução.
- `n_primeiras` = quantidade de questões **distintas** respondidas e que contam no assunto (é o que sustenta a nota). Repetição não aumenta.
- P usa o `theta` **antes** da atualização. Ordem de aplicação: `respondida_em`, depois `id` (permite recalcular por replay, com o mesmo resultado).
- **Exibir** só com `n_primeiras >= 6`; antes disso: "faltam N respostas pra liberar a nota". Entre 6 e 14: selo "pouca amostra". Sempre o rótulo **"nota estimada"** e o aviso: "estimativa do Gabaritu, não é a nota oficial do ENEM nem TRI". Mostrar com "~" (ex.: "~ 530").
- Exigência de conteúdo: pelo menos 10 questões por assunto, senão o mínimo consome o assunto inteiro (ver etapa 4).

**Exemplo** (aluno novo, assunto Porcentagem, theta = 0, n_primeiras = 0, K = 0,50):

1. Média (b=0), acerta: P = 0,5000; theta = 0 + 0,5 × (1 − 0,5) = **0,2500**.
2. Difícil (b=+1), erra: P = 1/(1+e^0,75) = 0,3208; theta = 0,25 + 0,5 × (0 − 0,3208) = **0,0896**.
3. Fácil (b=−1), acerta: P = 1/(1+e^−1,0896) = 0,7483; theta = 0,0896 + 0,5 × (1 − 0,7483) = **0,2155**.
4. Nota interna = 500 + 21,55 = 522 (exibida 520), mas só 3 respostas: **não aparece ainda**. Faltam 3 pra liberar.

## 2. Evolução no tempo

Guardar **um snapshot por resposta que conta pra nota** (tabela só de inserção, `desempenho_assunto_historico`): `aluno_id`, `assunto_id`, `resposta_id`, `theta`, `nota` (inteira, sem arredondar a 10), `n_primeiras`, `calculado_em`. O volume é pequeno (centenas de linhas por aluno). O gráfico agrupa por dia (último snapshot do dia) e **só desenha pontos com `n_primeiras >= 6`**. Além do histórico, `desempenho_assunto` guarda o estado atual (`theta`, `n_primeiras`). Respostas que não contam (chute, anulada) não geram snapshot. Tudo apaga em cascata com o aluno.

## 3. Diagnóstico por tipo de erro

- **Janela:** últimas 50 respostas válidas da matéria (válida = não anulada e não chute rápido; inclui repetições e revisão, porque repetir o erro é sinal). Com filtro por assunto, mesma regra dentro do assunto.
- **Cálculo:** `erros` = respostas erradas na janela. Para cada etiqueta: `ocorrencias`; `X% = round(100 × ocorrencias / erros)`. Erro de alternativa sem etiqueta entra no denominador, mas nunca é exibido.
- **Mínimos:** só mostra se `erros >= 5`. Só exibe etiqueta com `ocorrencias >= 3`. Se nenhuma passa: "ainda pouco dado pra um diagnóstico".
- **Exibição:** até 3 etiquetas, em ordem decrescente: "Dos seus últimos N erros, X% foram por <etiqueta>".
- **Desempate:** (1) mais ocorrências; (2) erro mais recente; (3) menor `id` da etiqueta.

## 4. Revisão espaçada (Leitner, 3 caixas)

| Caixa | Intervalo até a próxima revisão |
| ----- | ------------------------------- |
| 1     | 1 dia                           |
| 2     | 4 dias                          |
| 3     | 14 dias                         |

- **Entra:** qualquer resposta errada (questionário, simulado ou revisão), incluindo chute errado → caixa 1, `proxima = hoje + 1`. Se já estava na revisão, volta pra caixa 1.
- **Acerta na revisão:** sobe uma caixa; `proxima = hoje + CAIXAS_DIAS[nova caixa]` (conta a partir do dia da resposta, não do vencimento). **Acerto na caixa 3 = sai da revisão** (3 acertos seguidos).
- **Erra na revisão:** volta pra caixa 1, `proxima = amanhã`.
- **Acerto fora da revisão** (refazendo questionário) não altera a fila. **Acerto rápido (chute) na revisão** não avança: `proxima = amanhã`.
- **Revisão do dia:** questões com `proxima <= hoje`, ordem: mais atrasada, depois menor caixa, depois `id`. **Máximo 10 por dia** (conta as respostas de revisão já feitas hoje; passou de 10, o resto fica para amanhã, sem perder nada). Backlog acima de 10: "mais N esperando amanhã".

## 5. Freio da zoeira (aprovado pelo dono em 19/11)

- **Gatilho:** `FREIO_ERROS = 3` erros seguidos na sessão (chute errado conta; resposta anulada não conta). Na 3ª, a reação daquela própria resposta já usa o modo ajuda.
- **Zera o contador:** qualquer acerto, ou fim da sessão (logout ou 30 min sem responder).
- **Modo ajuda:** o Peixoto ignora o nível escolhido e usa tom acolhedor, sem zoeira (chaves `freio_entrada`, `freio_erro`, `freio_acerto`, `freio_saida`), com a dica da etiqueta de erro. Vale para todos os níveis, inclusive Fofo. Dentro do modo ajuda o gatilho não dispara de novo.
- **Volta ao normal:** após 2 acertos seguidos (uma fala `freio_saida`, depois o nível do aluno) ou no fim da sessão. Estado na sessão do servidor, não no banco (apaga com a conta).

## 6. Teto de zoeira por idade (decidido, só registro)

| Idade        | Níveis liberados              |
| ------------ | ----------------------------- |
| 13–14        | Fofo e Sarcástico             |
| 15 em diante | Fofo, Sarcástico e Sem filtro |

Nível efetivo = o menor entre o escolhido e o teto, conferido no servidor a cada fala (não só na interface). Regra fixa em todos os níveis: nada de doença, deficiência, transtorno, corpo, aparência, religião ou origem como xingamento ou comparação. Detalhe técnico: como só temos o ano de nascimento, a idade para o teto é `ano_atual − ano_nascimento − 1` (menor idade possível), para nunca liberar nível acima do devido.

## 7. Casos de borda

- **Questão anulada:** a questão ganha `status = anulada` (motivo, data). Efeito: sai de questionários e da fila de revisão; suas respostas passam a não contar (nota, diagnóstico, freio); os `theta` e snapshots dos assuntos afetados são recalculados por replay; o resultado do questionário mostra "X acertos de Y válidas". Erro de gabarito não se edita: anula e cria questão nova.
- **Refazer o mesmo questionário:** permitido, gera nova tentativa (`tentativa` na `Resposta`). Cada resposta usa `peso` 0,25 na nota; erros entram/reiniciam a revisão; o diagnóstico considera normalmente.
- **Chute rápido (proporcional ao enunciado, decisão do dono 19/11):** `limite_ms = clamp(CHUTE_MS_POR_CARACTERE × caracteres(enunciado + alternativas), CHUTE_MS_MIN, CHUTE_MS_MAX)`, calculado ao salvar a questão e guardado nela (`chute_limite_ms`); `tempo_ms < limite_ms` é chute. Exemplo: enunciado+alternativas com 150 caracteres → 3000 ms (piso); 300 → 6000 ms; 600 → 8000 ms (teto). Tempo medido no servidor (do envio da questão à confirmação). Não conta pra nota nem pro diagnóstico (a etiqueta de um chute é aleatória); conta pro freio; erro entra na revisão. O Peixoto pode ter uma fala própria (`chute_rapido`).
- **Conta apagada:** remove em cascata tentativas, respostas, snapshots, desempenho, revisão, feedback vinculado e e-mail. Nada de agregado derivado que guarde o aluno: estatísticas por questão são calculadas na hora, não armazenadas.

## 8. Tabela de testes (`LEVI`)

| #   | Entrada                                                               | Esperado                                                                                      |
| --- | --------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| 1   | theta 0, n 0, média, acerta                                           | theta 0,2500                                                                                  |
| 2   | theta 0, n 0, média, erra                                             | theta −0,2500                                                                                 |
| 3   | theta 0, difícil, acerta                                              | P 0,2689; theta 0,3655                                                                        |
| 4   | theta 0, fácil, erra                                                  | P 0,7311; theta −0,3655                                                                       |
| 5   | sequência do exemplo (média ok, difícil erra, fácil ok)               | theta 0,2500, 0,0896, 0,2155; nota 522; não exibe (n 3)                                       |
| 6   | theta 0, n_primeiras 10, média, acerta                                | K 0,25; theta 0,1250                                                                          |
| 7   | theta 0, n 0, média, acerta, resposta repetida                        | K 0,125; theta 0,0625; `n_primeiras` não sobe                                                 |
| 8   | theta −2, fácil, erra                                                 | cálculo −2,1344; trava em −2,0; nota 300                                                      |
| 9   | 5 primeiras respostas / 6 primeiras respostas                         | sem nota / nota exibida com "pouca amostra" e "estimada"                                      |
| 10  | acerto em 4 s numa questão de 300 caracteres (limite 6 s)             | chute: sem efeito em nota e diagnóstico, sem snapshot; erro em 4 s entra na revisão caixa 1   |
| 11  | janela: 10 erros, etiquetas A=4, B=4, C=2; B teve o erro mais recente | exibe B 40% e A 40% (B primeiro), C não exibida (<3)                                          |
| 12  | janela com 4 erros / 5 erros com maior etiqueta = 2                   | "ainda pouco dado" nos dois casos                                                             |
| 13  | erra em 10/03; acerta 11/03, 15/03 e 29/03                            | caixa 1 (próx. 11/03), 2 (15/03), 3 (29/03), sai da revisão                                   |
| 14  | questão na caixa 3 e o aluno erra                                     | caixa 1, próxima = amanhã                                                                     |
| 15  | 12 questões vencidas                                                  | revisão do dia mostra 10, mais atrasadas primeiro; "mais 2 amanhã"                            |
| 16  | erro, erro, acerto, erro, erro, erro                                  | freio dispara só no 3º erro seguido (6ª resposta); depois 2 acertos seguidos voltam ao normal |
| 17  | aluno de 14 anos com nível Sem filtro salvo                           | fala sorteada no máximo Sarcástico; aos 15, Sem filtro liberado                               |
| 18  | questão anulada com 3 respostas do aluno                              | theta recalculado sem elas; sai da revisão; resultado "de Y válidas"                          |
| 19  | apagar conta com respostas, snapshots, revisão e feedback             | nenhuma linha com o aluno em qualquer tabela; sessão de freio descartada                      |
