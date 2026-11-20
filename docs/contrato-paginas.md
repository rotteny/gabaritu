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
auth.user: { id, apelido, serie, idade, preferencias: { zoeira: 'fofo'|'sarc'|'sem', zoeira_maxima: 'sarc'|'sem', som: boolean, musica: boolean, reduzir_movimento: boolean, ultima_materia: string|null } } | null
gabaritu: { email_contato: string, repositorio_url: string, cadastro_aberto: boolean }
```

## Tipo `Fala`
```ts
type Fala = { texto: string, humor: 'feliz'|'bravo'|'neutro'|'ajuda', variacao_id: number|null }
```
O humor dirige o estado do Peixoto: feliz, inchado (bravo), normal (neutro) ou modo ajuda, quando o freio está ativo (o Peixoto calmo, sem espinhos).

## Rotas e páginas

| Rota | Página (`resources/js/pages/`) | Props principais |
|---|---|---|
| `GET /` | `Landing.vue` (já existe) | — |
| `GET /cadastro` · `POST` | `auth/Cadastro.vue` | campos: apelido, email, senha, senha_confirmation, ano_nascimento, serie (`9ano`, `1em`, `2em`, `3em`, `cursinho`), aceite_privacidade. Menor de 13 → erro claro e sem criar conta. |
| `GET /entrar` · `POST` | `auth/Entrar.vue` | email, senha, lembrar. |
| `/esqueci-senha`, `/redefinir-senha/{token}` | `auth/EsqueciSenha.vue`, `auth/RedefinirSenha.vue` | fluxo do Fortify; e-mail com o tom do Peixoto. |
| `GET /inicio` | `Inicio.vue` | `materias: [{ slug, nome, area, nota: number|null, assuntos_total, revisao_hoje: number }]`, `revisao_hoje_total`, `fala`. Agrupado por área do ENEM. |
| `GET /materias/{materia}` | `Materia.vue` | `materia: { slug, nome }`, `assuntos: [{ slug, nome, nota: number|null, amostra_baixa: boolean, questionarios: [{ id, titulo, ordem, feito: boolean, melhor_acertos: number|null }] }]`, `fala`. |
| `POST /questionarios/{id}/tentativas` | redirect → `/tentativas/{t}` | cria a tentativa. |
| `GET /tentativas/{t}` | `Questao.vue` | ver abaixo. |
| `POST /tentativas/{t}/respostas` | JSON (axios/fetch, sem recarregar a página) | ver abaixo. |
| `GET /tentativas/{t}/resultado` | `Resultado.vue` | `acertos`, `total`, `erro_mais_comum: { etiqueta, nome, vezes }|null`, `nota_assunto: { antes, depois }|null`, `tem_erradas: boolean`, `fala`. Botões: revisar erradas (cria uma tentativa de revisão) e próximo questionário. |
| `GET /revisao` | redirect → tentativa de revisão do dia (até 10) ou `Inicio` com fala de "nada pra revisar". | — |
| `GET /desempenho` | `Desempenho.vue` | por matéria e assunto: `nota`, `amostra_baixa`, `evolucao: [{ data, nota }]`, `diagnostico: [{ etiqueta, nome, percentual }]` (regras em `regras-nota.md`), `fala`. |
| `GET /conta` · `PATCH /conta/preferencias` · `PATCH /conta/perfil` · `PUT /conta/senha` · `DELETE /conta` | `Conta.vue` | preferências salvam na hora (zoeira, som, reduzir movimento). Apagar a conta pede a senha e apaga tudo. |
| `GET /privacidade` | `Privacidade.vue` | pública; divertida, mas completa e verdadeira. |
| erros 404/500/419/429 | `Erro.vue` | `status`, `fala`. |

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
  proxima: { url: string } | { resultado_url: string },
  report: { url: string, motivos: { valor: 'enunciado' | 'imagem' | 'gabarito' | 'explicacao' | 'outro', rotulo: string }[], max_comentario: number }  // 300
}
```
Erro de validação ou questão já respondida → 422, com mensagem clara.

### `POST {report.url}` (= `/questoes/{questao}/reports`) → JSON, sem recarregar a página
Botão "Reportar erro" no painel de explicação (discreto; o formulário abre em dialog, a tela da questão continua sem scroll). Só depois de responder.
Request: `{ motivo: 'enunciado' | 'imagem' | 'gabarito' | 'explicacao' | 'outro', comentario?: string /* até 300 */ }`
- `201 { ok: true }`
- `422 { message }` já reportou esta questão (1 por aluno por questão) ou dado inválido (`errors`)
- `429 { message }` 20 reports no dia
- `403 { message }` ainda não respondeu a questão

## Sorteio de fala (backend)
- Chave por evento: `acerto`, `acerto_apos_erro`, `erro:<etiqueta>`, `erro_especifico:<questao>` (quando existe `fala_especifica`), `freio`, `boas_vindas`, `proxima`, `resultado:<faixa>`, `inicio`, `vazio:<tela>`, `erro_http:<status>`.
- Variações elegíveis: nível ≤ preferência efetiva, idade mínima, vigência. Preferência pelas variações que o aluno não viu nas últimas N respostas; reserva atemporal se nada sobrar.
- As falas das telas (início, vazio, erro, resultado, boas-vindas) também vêm de um pacote: `conteudo/falas-interface.json` no repo, com o mesmo formato de nível e reserva.

## Acréscimos do backend (implementados)

- **Resultado:** `tentativa_id`, `proximo_questionario`, `tipo`. "Revisar erradas" é `POST /tentativas/{id}/revisao` (redirect). A tentativa criada tem `tipo: 'revisao'` no contrato (internamente `revisao_erradas`, que não consome a cota de 10/dia).
- **Início:** além do contrato, `revisao_amanha` (backlog acima do teto de 10). `GET /revisao` sem nada vencido redireciona para `/inicio` com a fala `vazio:revisao`.
- **Matéria:** cada assunto traz também `faltam` (respostas que faltam para liberar a nota).
- **Desempenho:** `materias[]` com `nota`, `amostra_baixa`, `diagnostico`, `diagnostico_erros`, `assuntos[]` (com `evolucao`, `faltam`) e `revisao: {hoje, amanha}`. Nota exibida sempre arredondada de 10 em 10; `diagnostico[].etiqueta` é o código da etiqueta.
- **Campos em português:** login (`email`, `senha`, `lembrar`), redefinição (`POST /redefinir-senha` com `token`, `email`, `senha`, `senha_confirmation`, erros na chave `senha`), conta (`senha_atual`, `senha`, `senha_confirmation`; `DELETE /conta` com `senha`). Erro de credencial no login vem na chave `email`.
- **Cadastro:** `GET /cadastro` recebe `series` e `idade_minima`. Menor de 13: erro na chave `ano_nascimento`.
- **Preferências:** `zoeira` acima do teto da idade responde 422 na chave `zoeira`.
- **Falas extras** no pacote de interface: `erro`, `freio_saida`, `materia`, `desempenho`, `privacidade`. As falas `freio*` só têm nível `fofo` (modo ajuda ignora o nível).
- **Recuperação de senha:** resposta idêntica para e-mail existente ou não (`status`).
- Starter kit removido: 2FA, passkeys, verificação de e-mail, `/dashboard` e `/settings/*` (as páginas `.vue` antigas ficam para a `BULMA` apagar).

## Responsável legal (ECA Digital)
O fluxo de responsável (cadastro do menor, convites, painel `/responsavel`, login e recuperação de senha próprios, pedido iniciado pelo responsável, contestação do aluno e e-mails) está em `docs/responsaveis.md`, que é a fonte única para essas rotas e props. Mudanças no contrato existente: `auth.user` ganha `tipo`, `status` e `avatar`; `POST /cadastro` ganha `email_responsavel`, `parentesco_responsavel`, `parentesco_outro`, `avatar`, `convite` e `confirma_responsavel`; `GET /conta` ganha `responsaveis`, `convites_responsavel`, `pedidos_vinculo`, `pode_convidar` e `avatares`; `/termos` é pública mesmo com o cadastro fechado; `/desempenho` e o painel do responsável usam o mesmo serviço (`DesempenhoDoAluno`). O teto de zoeira efetivo (`auth.user.preferencias.zoeira_maxima`) é o menor entre o da idade e o do responsável. O aluno tem `avatar` escolhido de uma lista fixa em `config('gabaritu.avatares')` (12 bichos marinhos), editável em `PATCH /conta/perfil` e sorteado no cadastro; não existe upload de foto.
