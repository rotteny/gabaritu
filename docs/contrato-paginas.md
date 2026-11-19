# Contrato entre backend e páginas (MVP)

Este documento é a fonte única de verdade entre o `ESCANOR` (backend) e a `BULMA` (páginas Inertia/Vue). Cada um trabalha na sua branch e o merge acontece no fim. Se precisar mudar o contrato, mude este arquivo nas duas branches e avise.

Referências: `docs/escopo-mvp.md` e `docs/regras-nota.md`. Conteúdo em `~/workspace/gabaritu-conteudo/materias/*` (formato em `FORMATO.md`).

## Regras transversais

- **Tom lúdico em todas as telas,** com o Peixoto e o visual cartoon da landing (`resources/js/components/landing/`, `resources/css/landing/`). Em erro de senha, privacidade, exclusão e consentimento, a frase clara vem primeiro e a piada depois.
- **Texto com meme nunca fica fixo no código.** Toda fala vem do backend, pelo catálogo (prop `fala`). Rótulos de botão e campo são texto fixo e claro.
- **A tela de questão não tem scroll vertical** (360×640 a 1366×768). As outras telas podem rolar.
- **Alternativas embaralhadas na exibição,** por aluno e por tentativa. O backend manda as alternativas já embaralhadas, com `id` estável, e a letra exibida (A–E) é só posição. Motivo: no banco gerado, a sequência de letras corretas se repete a cada 20 questões.
- **Preferências do aluno** (nível de zoeira, som, reduzir movimento, última matéria) ficam no servidor. O teto de zoeira por idade é aplicado no servidor: 13–14 anos, no máximo `sarc`; 15 em diante, `sem` liberado. Idade = ano atual − ano de nascimento − 1.
- **Cadastro fechado** (`gabaritu.cadastro_aberto=false`): tudo fora da landing dá 404. Em dev fica aberto.

## Prop compartilhada (HandleInertiaRequests)

```ts
auth.user: { id, apelido, serie, idade, preferencias: { zoeira: 'fofo'|'sarc'|'sem', zoeira_maxima: 'sarc'|'sem', som: boolean, reduzir_movimento: boolean, ultima_materia: string|null } } | null
gabaritu: { email_contato: string, repositorio_url: string, cadastro_aberto: boolean }
```

## Tipo `Fala`

```ts
type Fala = {
    texto: string;
    humor: 'feliz' | 'bravo' | 'neutro' | 'ajuda';
    variacao_id: number | null;
};
```

O humor dirige o estado do Peixoto: feliz, inchado (bravo), normal (neutro) ou modo ajuda, quando o freio está ativo (o Peixoto calmo, sem espinhos).

## Rotas e páginas

| Rota                                                                                                      | Página (`resources/js/pages/`)                                                              | Props principais                                                                                                                                                                    |
| --------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /`                                                                                                   | `Landing.vue` (já existe)                                                                   | —                                                                                                                                                                                   |
| `GET /cadastro` · `POST`                                                                                  | `auth/Cadastro.vue`                                                                         | campos: apelido, email, senha, senha_confirmation, ano_nascimento, serie (`9ano`, `1em`, `2em`, `3em`, `cursinho`), aceite_privacidade. Menor de 13 → erro claro e sem criar conta. |
| `GET /entrar` · `POST`                                                                                    | `auth/Entrar.vue`                                                                           | email, senha, lembrar.                                                                                                                                                              |
| `/esqueci-senha`, `/redefinir-senha/{token}`                                                              | `auth/EsqueciSenha.vue`, `auth/RedefinirSenha.vue`                                          | fluxo do Fortify; e-mail com o tom do Peixoto.                                                                                                                                      |
| `GET /inicio`                                                                                             | `Inicio.vue`                                                                                | `materias: [{ slug, nome, area, nota: number                                                                                                                                        | null, assuntos_total, revisao_hoje: number }]`, `revisao_hoje_total`, `fala`. Agrupado por área do ENEM.  |
| `GET /materias/{materia}`                                                                                 | `Materia.vue`                                                                               | `materia: { slug, nome }`, `assuntos: [{ slug, nome, nota: number                                                                                                                   | null, amostra_baixa: boolean, questionarios: [{ id, titulo, ordem, feito: boolean, melhor_acertos: number | null }] }]`, `fala`.                                                                                                   |
| `POST /questionarios/{id}/tentativas`                                                                     | redirect → `/tentativas/{t}`                                                                | cria a tentativa.                                                                                                                                                                   |
| `GET /tentativas/{t}`                                                                                     | `Questao.vue`                                                                               | ver abaixo.                                                                                                                                                                         |
| `POST /tentativas/{t}/respostas`                                                                          | JSON (axios/fetch, sem recarregar a página)                                                 | ver abaixo.                                                                                                                                                                         |
| `GET /tentativas/{t}/resultado`                                                                           | `Resultado.vue`                                                                             | `acertos`, `total`, `erro_mais_comum: { etiqueta, nome, vezes }                                                                                                                     | null`, `nota_assunto: { antes, depois }                                                                   | null`, `tem_erradas: boolean`, `fala`. Botões: revisar erradas (cria uma tentativa de revisão) e próximo questionário. |
| `GET /revisao`                                                                                            | redirect → tentativa de revisão do dia (até 10) ou `Inicio` com fala de "nada pra revisar". | —                                                                                                                                                                                   |
| `GET /desempenho`                                                                                         | `Desempenho.vue`                                                                            | por matéria e assunto: `nota`, `amostra_baixa`, `evolucao: [{ data, nota }]`, `diagnostico: [{ etiqueta, nome, percentual }]` (regras em `regras-nota.md`), `fala`.                 |
| `GET /conta` · `PATCH /conta/preferencias` · `PATCH /conta/perfil` · `PUT /conta/senha` · `DELETE /conta` | `Conta.vue`                                                                                 | preferências salvam na hora (zoeira, som, reduzir movimento). Apagar a conta pede a senha e apaga tudo.                                                                             |
| `GET /privacidade`                                                                                        | `Privacidade.vue`                                                                           | pública; divertida, mas completa e verdadeira.                                                                                                                                      |
| erros 404/500/419/429                                                                                     | `Erro.vue`                                                                                  | `status`, `fala`.                                                                                                                                                                   |

### `Questao.vue` (`GET /tentativas/{t}`)

```ts
tentativa: { id, tipo: 'questionario'|'revisao', titulo, materia: {slug,nome}, assunto: {slug,nome}, posicao: number, total: number }
questao: { id, contexto: string|null, enunciado: string, alternativas: [{ id, letra: 'A'..'E', texto }] }
fala: Fala   // abertura (boas-vindas ou próxima)
```

O servidor grava `servida_em` ao entregar a questão (o tempo de chute é medido no servidor).

### `POST /tentativas/{t}/respostas` → JSON

Request: `{ questao_id, alternativa_id }`

```ts
{
  correta: boolean,
  alternativa_correta_id: number,
  etiqueta: { id, nome } | null,      // da alternativa escolhida, se errada
  explicacao: string,
  fala: Fala,                         // sorteada por aluno, sem repetir as recentes; respeita nível, idade, freio e validade
  freio_ativo: boolean,
  chute: boolean,                     // tempo menor que o limite da questão (não aparece pro aluno como acusação)
  proxima: { url: string } | { resultado_url: string }
}
```

Erro de validação ou questão já respondida → 422, com mensagem clara.

## Sorteio de fala (backend)

- Chave por evento: `acerto`, `acerto_apos_erro`, `erro:<etiqueta>`, `erro_especifico:<questao>` (quando existe `fala_especifica`), `freio`, `boas_vindas`, `proxima`, `resultado:<faixa>`, `inicio`, `vazio:<tela>`, `erro_http:<status>`.
- Variações elegíveis: nível ≤ preferência efetiva, idade mínima, vigência. Preferência pelas variações que o aluno não viu nas últimas N respostas; reserva atemporal se nada sobrar.
- As falas das telas (início, vazio, erro, resultado, boas-vindas) também vêm de um pacote: `conteudo/falas-interface.json` no repo, com o mesmo formato de nível e reserva.

## Acréscimos da `BULMA` (a confirmar com o `ESCANOR`)

O contrato original não fechava estes pontos; as páginas já usam o que está abaixo.

- **`Resultado.vue`** recebe também `tentativa_id: number` e `proximo_questionario: { id, titulo } | null`. "Revisar as erradas" faz `POST /tentativas/{tentativa_id}/revisao` (redirect para a tentativa de revisão); "Próximo questionário" usa `POST /questionarios/{id}/tentativas`. `nota_assunto` aceita `antes`/`depois` nulos.
- **`Desempenho.vue`**: `materias: [{ slug, nome, nota, amostra_baixa, evolucao, diagnostico, assuntos: [{ slug, nome, nota, amostra_baixa, evolucao, diagnostico }] }]` e `fala`. `evolucao` com `data` em `AAAA-MM-DD`.
- **`Privacidade.vue`**: `fala` é opcional. O texto da página é informativo e fixo; só a fala vem do pacote.
- **Campos em português** (Fortify precisa mapear): login `email`, `senha`, `lembrar`; recuperação `email`; redefinição `token`, `email`, `senha`, `senha_confirmation` (`POST /redefinir-senha`); página de redefinição recebe `token` e `email`. Conta: `PUT /conta/senha` com `senha_atual`, `senha`, `senha_confirmation`; `DELETE /conta` com `senha`; `PATCH /conta/preferencias` com qualquer um de `zoeira`, `som`, `reduzir_movimento`; `PATCH /conta/perfil` com `apelido`, `serie`.
- **`Entrar.vue`** e **`EsqueciSenha.vue`** aceitam `status` (string ou nulo) para a mensagem de confirmação.
- **Privacidade (texto)**: além dos dados do contrato (apelido, ano de nascimento, série, e-mail, respostas), a página declara o tempo de resposta (medido no servidor para detectar chute) e as preferências, porque o backend os guarda. Revisar com o dono antes de publicar.
- **Prévia local**: `routes/previa.php` (só `APP_ENV=local`) serve `/_previa/{pagina}` com fixtures de `resources/js/fixtures/previa/`. Não deve ir para produção; o merge precisa manter o `if (app()->isLocal())` em `routes/web.php`.
