# Painel admin (v1)

Fonte única entre o `ESCANOR` (backend) e a `BULMA` (páginas). Decisões do dono em 20/11.

## Decisões
- **Só o dono, via comando.** A tabela `admins` é separada de `users` e `responsaveis`, com guard `admin` próprio. Não existe cadastro nem recuperação de senha por e-mail: a conta é criada e a senha trocada só por comando (`php artisan gabaritu:admin criar|senha|2fa-reset {email}`).
- **2FA TOTP obrigatório.** Funciona com app autenticador e tem 8 códigos de recuperação de uso único, guardados com hash. No primeiro login, o admin é obrigado a configurar o 2FA (QR em SVG gerado no servidor). Sem 2FA confirmado, nenhuma rota do painel abre.
- **Dados mínimos + log.** O e-mail aparece mascarado (`jo***@gmail.com`). "Revelar" pede um motivo curto e grava em `admin_auditoria` (admin, ação, alvo, motivo, quando). Toda abertura de ficha de aluno ou responsável também é registrada. Ninguém edita a auditoria pelo painel.
- **Acessos agregados.** A tabela `metricas_diarias` soma por dia: `alunos_ativos` (únicos), `logins`, `respostas` e `visitas_landing`. Não guarda IP, user-agent nem id: a contagem de únicos usa um conjunto diário no cache, que é descartado depois. Os cadastros por dia saem do `created_at`, desde o início.

## Acesso e segurança
- O prefixo de rota vem de `config('gabaritu.admin_prefixo')` (env `GABARITU_ADMIN_PREFIXO`, padrão `admin`) e fica fora do sitemap e com `noindex`.
- O login tem rate limit forte (5 tentativas por minuto por IP e por e-mail) e mensagem genérica.
- A sessão do admin fica separada da sessão do aluno e expira em 30 minutos sem uso. Ações sensíveis, como revelar e-mail, pedem uma reconfirmação de TOTP a cada 15 minutos.
- Não há edição de dado pessoal de aluno pelo painel na v1, só leitura. A única escrita é o status jurídico de questão INEP (ver abaixo).
- A CSP continua valendo: os gráficos não podem depender de `eval` nem de script inline.

## Telas
1. **Dashboard:**
   - **Cards:** total de alunos (por status), responsáveis, cadastros hoje, 7 dias e 30 dias, alunos ativos hoje e em 7 dias, respostas hoje.
   - **Gráficos:** cadastros por dia (30, 90 dias ou tudo), ativos, logins e respostas por dia, e o funil cadastro → responsável autorizou (só para menor de 18) → primeira resposta → voltou em outro dia.
2. **Alunos:**
   - **Busca:** por apelido (prefixo) ou e-mail EXATO (nunca por prefixo, para não reconstruir o endereço sem "revelar"), com filtros de status, série e "tem responsável".
   - **Lista:** apelido, e-mail mascarado, série, idade, status, cadastro, última atividade (data, sem hora) e nº de respostas.
   - **Ficha:** os mesmos dados, mais responsáveis vinculados (e-mail mascarado e parentesco), pedidos e convites pendentes, desempenho resumido por matéria e o botão "revelar e-mail".
3. **Responsáveis:**
   - **Busca:** por e-mail exato.
   - **Lista:** e-mail mascarado, nº de alunos vinculados, convites pendentes e cadastro.
   - **Ficha:** os alunos vinculados, só pelo apelido, e o botão de revelar.
4. **Questões:**
   - **Por matéria e assunto:** total, quantas autorais e quantas INEP, respostas e % de acerto.
   - **Lista filtrável:** origem, matéria, assunto, status jurídico e "suspeita". Cada questão mostra respostas, % de acerto, tempo médio e distribuição por alternativa.
   - **Suspeitas:** com ≥ 20 respostas, acerto < 15% ou > 95%, ou um distrator mais escolhido que a correta.
   - **Status jurídico INEP:** pode ser trocado na ficha (`pendente` | `ok` | `remover`), com auditoria. `remover` tira a questão do ar no próximo reimport e já a esconde na hora.
5. **Respostas:** a série diária de respostas, as matérias e os questionários mais feitos, a taxa de abandono de questionário e as etiquetas de erro mais frequentes.

## Visual
Fica no tema do Gabaritu, mas sóbrio: sidebar à esquerda e tema claro, sem Peixoto falando nem zoeira. Precisa funcionar bem no desktop e ser usável no celular. Os gráficos usam uma biblioteca leve, compatível com a CSP, e respeitam `prefers-reduced-motion`.

## Acréscimos aprovados pelo dono (20/11, mesma v1)
6. **Reportar erro na questão (aluno):** na tela da questão, depois de responder, o aluno toca em "Reportar erro" e escolhe um motivo: enunciado ou texto com problema, imagem com problema, gabarito parece errado, explicação errada ou outro. Pode escrever um comentário opcional de até 300 caracteres. Limite: 1 report por aluno por questão e 20 por dia. O link entre o report e o aluno fica guardado, para evitar abuso, mas o painel mostra só o apelido. No admin, a tela **Reports** lista a fila agrupada por questão, com contagem por motivo e status `aberto | resolvido | descartado` (com auditoria). O report também conta como sinal de "suspeita". A tela do aluno não pode ganhar scroll (contrato: a tela de questão não rola), então o botão fica discreto no painel de explicação e o formulário abre num dialog.
7. **Retenção:** coortes por semana de cadastro, com a % de alunos que voltaram em D1, D7 e D30. "Voltou" é responder alguma coisa num dia diferente do dia do cadastro. O cálculo usa só as datas das respostas, que já existem, sem coleta nova.
8. **Pendências legais:** autorizações de responsável paradas há mais de 7 dias, convites expirados, contestações de vínculo ("não é meu responsável"), pedidos de vínculo iniciados pelo responsável e ainda pendentes, e contagem de contas apagadas por mês (só o número; conta apagada não deixa dado).
9. **Saúde do sistema:** e-mails que falharam (fila `failed_jobs`, contagem e últimas falhas sem o corpo da mensagem), data do último backup local (lida do diretório de backup, via config), último tick do scheduler (heartbeat gravado no cache a cada execução), versão do deploy (commit) e totais do INEP pelo `gabaritu:inep --relatorio`.
10. **Falas do Peixoto:** usa o `FeedbackFala`, se ele registrar curtida ou rejeição. Mostra as falas mais curtidas e as mais rejeitadas, por nível de zoeira e por chave. Se o modelo não tiver esse dado, a tela mostra as falas mais exibidas e a seção fica marcada "sem feedback".
11. **Exportar CSV** só com números agregados: métricas diárias, cadastros por dia, questões com % de acerto e retenção. Nenhum e-mail ou apelido sai no CSV. Cada exportação fica registrada na auditoria.

## Decisões do backend além da spec
- **Status jurídico:** os valores guardados são os do importador, `pendente | liberada | remover` (a spec dizia `ok`; mantive `liberada` para não ter dois vocabulários). `pendente` pode estar como `null` no banco e é tratado igual. O reimport do INEP **não** rebaixa para `pendente` uma decisão já tomada no painel (só um `liberada`/`remover` explícito no JSON a muda).
- **Sessão separada:** sob o prefixo do admin o cookie é `<cookie-do-site>_admin`, com `Path=/<prefixo>`, `SameSite=Strict` e vida de 30 min; o `XSRF-TOKEN` também sai com esse caminho. Login do aluno e do admin nunca se renovam nem se derrubam. A sessão cai se a senha mudar pelo comando.
- **Rotas vêm do servidor:** como o prefixo é configurável em produção, o front não deve depender das rotas do wayfinder (geradas com o prefixo do build). Use as URLs das props (`admin.rotas`, `url`, `acoes`). Os nomes de rota (`admin.*`) estão no fim, para referência.
- **2FA:** cada código TOTP vale uma vez (passo guardado); login com recuperação consome o código. Limite de 5 tentativas por minuto por admin e por IP no 2FA e no "revelar". Admin sem 2FA fica logado só com a senha, mas toda rota do painel o manda para configurar.
- **Ativos:** `alunos_ativos` do dia = alunos que entraram ou responderam (únicos, token HMAC diário no cache por 36 h). "Ativos em 7 dias" do dashboard é calculado por `ultimo_acesso_em` ou resposta nos últimos 7 dias. `visitas_landing` inclui robôs (não guardamos user-agent para filtrá-los). `respostas` foi preenchido retroativamente a partir da tabela `respostas`; as demais métricas começam na data do deploy.
- **Funil:** cadastro → liberado (adulto, ou menor com `status=ativo`) → primeira resposta → voltou em outro dia (respostas em 2 ou mais dias locais), sobre os cadastros do período.
- **Suspeita:** a spec, mais `reports` (3 ou mais reports abertos; `gabaritu.admin.suspeita.min_reports`).
- **Retenção:** `Dn` = respondeu em algum dia entre o dia seguinte ao cadastro e n dias depois (janela acumulada). O aluno só entra na base de Dn quando o dia n já passou; coorte sem base mostra `pct: null`.
- **Falas:** `FeedbackFala.legal` é a curtida (`true`) ou rejeição (`false`), então o ranking de feedback existe. Sem nenhuma linha, `tem_feedback=false` e a tela usa `mais_exibidas` (`respostas.variacao_fala_id`).
- **Contas apagadas:** não havia registro, então o evento `deleted` do `User` incrementa `contas_apagadas_mes` (só o número, na mesma transação). Só vale daqui para a frente.
- **Saúde:** o diretório de backup vem de `GABARITU_BACKUP_DIR` e a versão de `GABARITU_VERSAO` (o deploy precisa exportar o commit; ainda não exporta: pendência para o `SAITAMA`). O heartbeat do scheduler é o job `heartbeat` (a cada minuto, `Cache::forever`); atrasado após 5 min.
- **Erros no painel** (404, 403…) saem sem o Peixoto.

## Contrato de props

Todas as páginas são Inertia sob o prefixo configurável (`gabaritu.admin_prefixo`). Props compartilhadas do admin, só quando logado:

```ts
type Paginado<T> = { dados: T[]; pagina: number; por_pagina: number; total: number; ultima_pagina: number }

// shared props (usePage().props)
admin: null | {
  email: string; prefixo: string; inatividade_minutos: number  // 30
  restantes_recuperacao: number | null   // só logo depois de entrar com código de recuperação
  rotas: { dashboard, alunos, responsaveis, questoes, respostas, reports, retencao, pendencias, saude, falas, sair: string }  // sair = POST
  exportar: { metricas, cadastros, questoes, retencao: string }   // GET que baixa CSV
}
```
`auth.user` é sempre o aluno do site (nulo no painel).

Erros de formulário vêm em `errors` (padrão Inertia). Datas: `YYYY-MM-DD` no fuso de São Paulo; tempos em ms; porcentagens com 1 casa (0 a 100) ou `null` sem dados.

### Rotas (nomes; URL real nas props)
`admin.login` GET/`admin.login.store` POST `/entrar` · `admin.logout` POST `/sair` · `admin.dois-fatores` GET `/dois-fatores` · `admin.dois-fatores.confirmar` POST · `admin.dois-fatores.desafiar` POST · `admin.dashboard` GET `/` · `admin.alunos` `/alunos` · `admin.alunos.show` `/alunos/{aluno}` · `admin.alunos.revelar-email` POST · `admin.responsaveis` `/responsaveis` · `admin.responsaveis.show` · `admin.responsaveis.revelar-email` POST · `admin.questoes` `/questoes` · `admin.questoes.show` `/questoes/{questao}` · `admin.questoes.status-juridico` PATCH · `admin.respostas` `/respostas` · `admin.reports` `/reports` · `admin.reports.update` PATCH `/reports/{report}` · `admin.reports.questao` PATCH `/reports/questao/{questao}` · `admin.retencao` · `admin.pendencias` · `admin.saude` · `admin.falas` · `admin.exportar` GET `/exportar/{tipo}`.

### `admin/Login`
```ts
{ status: 'sessao_expirada' | null; acoes: { entrar: string } }
```
POST `{ email, senha }`. Erro sempre em `errors.email` (mensagem genérica, ou "Muitas tentativas…" depois de 5 por minuto). Sucesso redireciona para `/dois-fatores`.

### `admin/DoisFatores`
```ts
type Acoes = { confirmar: string; desafiar: string; continuar: string; sair: string }
type Props =
  | { modo: 'configurar'; segredo: string; qr_svg: string /* SVG pronto, usar v-html */; otpauth_url: string; acoes: Acoes }
  | { modo: 'desafiar'; acoes: Acoes }
  | { modo: 'codigos'; codigos: string[] /* 8, aparecem uma única vez */; acoes: Acoes }
```
- `configurar`: POST `acoes.confirmar` `{ codigo }` (6 dígitos). Erro em `errors.codigo`. Sucesso mostra `modo: 'codigos'` na mesma rota; o botão "continuar" vai para `acoes.continuar`.
- `desafiar`: POST `acoes.desafiar` `{ codigo }` **ou** `{ recuperacao }` (formato `xxxxxx-xxxxxx`). Erros em `errors.codigo` / `errors.recuperacao`.

### `admin/Dashboard` (GET `?periodo=30|90|tudo`)
```ts
{
  periodo: '30' | '90' | 'tudo'
  cartoes: {
    alunos: { total: number; por_status: { ativo: number; aguardando_responsavel: number } }
    responsaveis: number
    cadastros: { hoje: number; ultimos_7_dias: number; ultimos_30_dias: number }
    ativos: { hoje: number; ultimos_7_dias: number }
    respostas_hoje: number
  }
  funil: {
    etapas: { chave: 'cadastro' | 'liberado' | 'primeira_resposta' | 'voltou'; rotulo: string; valor: number }[]  // 4 etapas, decrescentes em geral
    menores: { cadastrados: number; autorizados: number }   // detalhe da etapa "liberado"
  }
  series: { dia: string; cadastros: number; alunos_ativos: number; logins: number; respostas: number; visitas_landing: number }[]  // um item por dia do período, com zeros; ordem crescente
}
```

### `admin/Alunos` (GET `?busca=&status=&serie=&tem_responsavel=sim|nao&page=`)
```ts
{
  filtros: { busca: string | null; status: string | null; serie: string | null; tem_responsavel: 'sim' | 'nao' | null }
  opcoes: { status: ('ativo' | 'aguardando_responsavel')[]; series: { valor: string; rotulo: string }[] }
  alunos: Paginado<AlunoLinha>
}
type AlunoLinha = {
  id: number; apelido: string; email_mascarado: string; serie: string | null; serie_rotulo: string | null; idade: number | null
  status: string; cadastro: string; ultima_atividade: string | null; respostas: number; tem_responsavel: boolean; url: string
}
```
Busca: apelido (contém) ou e-mail exato (inteiro, sem diferenciar maiúsculas). 25 por página.

### `admin/Aluno` (abrir a ficha grava auditoria)
```ts
{
  aluno: AlunoLinha
  responsaveis: { id: number; email_mascarado: string; parentesco: string; parentesco_outro: string | null; status: 'ativo' | 'suspenso'; aceito_em: string | null; url: string }[]
  pedidos_e_convites: { id: number; tipo: string; email_mascarado: string; parentesco: string; expira_em: string | null }[]
  desempenho: { materia: string; nota_media: number | null; assuntos_com_nota: number; respostas: number; pct_acerto: number | null }[]
  revelar: { url: string; exige_codigo: boolean; email: string | null }
}
```
**Revelar e-mail:** POST `revelar.url` `{ motivo: string /* 5 a 200 */; codigo?: string }`. `codigo` (TOTP de 6 dígitos) é obrigatório quando `exige_codigo` for `true` (reconfirmação vencida, 15 min). Erros em `errors.motivo` / `errors.codigo`. Sucesso redireciona para a mesma ficha, com `revelar.email` preenchido **só nessa visita** (flash): mostrar e, se quiser, limpar da tela depois de alguns segundos.

### `admin/Responsaveis` (GET `?busca=&page=`; busca pelo e-mail exato)
```ts
{ filtros: { busca: string | null }; responsaveis: Paginado<{ id: number; email_mascarado: string; alunos_vinculados: number; convites_pendentes: number; cadastro: string; url: string }> }
```

### `admin/Responsavel` (grava auditoria)
```ts
{
  responsavel: { id: number; email_mascarado: string; alunos_vinculados: number; convites_pendentes: number; cadastro: string; url: string }
  alunos: { id: number; apelido: string | null; parentesco: string; status: 'ativo' | 'suspenso'; url: string }[]   // só apelido
  revelar: { url: string; exige_codigo: boolean; email: string | null }   // igual ao do aluno
}
```

### `admin/Questoes` (GET `?origem=autoral|inep&materia=<id>&assunto=<id>&status_juridico=pendente|liberada|remover&suspeita=1&page=`)
```ts
{
  filtros: { origem, materia, assunto, status_juridico, suspeita: string | null }
  opcoes: { materias: { id: number; nome: string }[]; assuntos: { id: number; materia_id: number; nome: string }[]; status_juridico: ('pendente' | 'liberada' | 'remover')[] }
  resumo: {
    suspeitas: number
    materias: { id: number; nome: string; total: number; autorais: number; inep: number; respostas: number; pct_acerto: number | null
                assuntos: { id: number; nome: string; total: number; autorais: number; inep: number; respostas: number; pct_acerto: number | null }[] }[]
  }
  questoes: Paginado<QuestaoLinha>
}
type QuestaoLinha = {
  id: number; codigo: string; origem: 'autoral' | 'inep'; materia: string | null; assunto: string | null; area: string | null
  status_juridico: 'pendente' | 'liberada' | 'remover' | null   // null para autoral
  respostas: number; pct_acerto: number | null; tempo_medio_ms: number | null
  distribuicao: { letra: string; correta: boolean; total: number; pct: number | null }[]
  reports_abertos: number
  suspeita: boolean; motivos_suspeita: ('acerto_baixo' | 'acerto_alto' | 'distrator_domina' | 'reports')[]
  url: string
}
```

### `admin/Questao`
```ts
{
  questao: QuestaoLinha & {
    dificuldade: number; status: string; contexto: string | null; enunciado: string; explicacao: string | null; imagem_url: string | null; fonte: string | null
    distribuicao: (QuestaoLinha['distribuicao'][number] & { texto: string | null; etiqueta: string | null })[]
    historico_juridico: { de: string | null; para: string | null; quando: string; admin: string }[]
  }
  opcoes: { status_juridico: string[] }
  acoes: { status_juridico: string | null /* null se autoral */; voltar: string }
}
```
PATCH `acoes.status_juridico` `{ status: 'pendente' | 'liberada' | 'remover' }` (só INEP; autoral dá 404). `remover` esconde para o aluno na hora e fica registrado na auditoria.

### `admin/Respostas` (GET `?periodo=30|90|tudo`)
```ts
{
  periodo: '30' | '90' | 'tudo'
  serie: { dia: string; respostas: number }[]
  materias: { id: number; nome: string; respostas: number; pct_acerto: number | null }[]          // top 10
  questionarios: { id: number; titulo: string; assunto: string; tentativas: number; concluidas: number }[]   // top 10
  abandono: { iniciadas: number; concluidas: number; abandonadas: number; taxa_pct: number | null }  // abandonada = sem finalizar há mais de 24 h
  etiquetas_de_erro: { id: number; nome: string; materia: string; total: number }[]                // top 10
}
```

### `admin/Reports` (GET `?status=aberto|resolvido|descartado&page=`; padrão `aberto`)
```ts
{
  filtros: { status: 'aberto' | 'resolvido' | 'descartado' }
  opcoes: { status: string[]; motivos: { valor: string; rotulo: string }[] }
  contagens: { aberto: number; resolvido: number; descartado: number }
  fila: Paginado<{
    questao: { id: number; codigo: string; origem: string; materia: string | null; assunto: string | null; url: string }
    total: number
    por_motivo: { enunciado: number; imagem: number; gabarito: number; explicacao: number; outro: number }
    reports: { id: number; apelido: string; motivo: string; comentario: string | null; status: string; criado_em: string; url: string }[]
    atualizar_todos: string
  }>
}
```
PATCH `reports[].url` `{ status: 'aberto' | 'resolvido' | 'descartado' }` (um report) · PATCH `atualizar_todos` `{ status: 'resolvido' | 'descartado' }` (todos os abertos da questão). Ambos voltam para a mesma tela e gravam auditoria. O e-mail do aluno nunca sai.

### `admin/Retencao`
```ts
{
  marcos: number[]   // [1, 7, 30]
  coortes: ({ semana: string /* segunda-feira */; cadastros: number } & Record<`d${number}`, { base: number; voltaram: number; pct: number | null }>)[]   // 12 semanas, mais recente primeiro
}
```

### `admin/Pendencias`
```ts
{
  autorizacoes_paradas: { dias: number /* 7 */; total: number; itens: { id: number; apelido: string; parado_ha_dias: number; email_responsavel_mascarado: string | null; url: string }[] }
  convites_expirados: number
  contestacoes: { total: number; itens: { aluno_id: number; aluno: string | null; parentesco: string; desde: string | null; aluno_url: string; responsavel_url: string }[] }
  pedidos_de_responsavel: { total: number; itens: { id: number; tipo: string; email_mascarado: string; parentesco: string; criado_em: string | null; expira_em: string | null }[] }
  contas_apagadas_por_mes: { mes: string /* YYYY-MM */; alunos: number; responsaveis: number }[]
}
```
As listas trazem até 50 itens (`total` é a contagem completa).

### `admin/Saude`
```ts
{
  falhas: { total: number; ultimas: { id: number; fila: string; job: string | null; erro: string | null /* só a classe */; quando: string }[] }
  backup: { configurado: boolean; arquivo: string | null; quando: string | null /* YYYY-MM-DD HH:mm */; idade_horas: number | null }
  scheduler: { ultimo_tick: string | null; atrasado: boolean }   // atrasado: sem tick há 5 min ou mais
  versao: string   // commit do deploy, ou 'desconhecida'
  inep: { exibicao_ligada: boolean; total: number; por_ano: Record<string, number>; por_status: Record<string, number> }
}
```

### `admin/Falas`
```ts
{
  tem_feedback: boolean   // false: só `mais_exibidas` vem preenchida (seção marcada "sem feedback")
  mais_curtidas: FalaRank[]; mais_rejeitadas: FalaRank[]
  por_nivel: { grupo: string; curtidas: number; rejeicoes: number }[]
  por_chave: { grupo: string; curtidas: number; rejeicoes: number }[]   // top 10
  mais_exibidas: { id: number; texto: string; nivel: string; chave: string; exibicoes: number }[]
}
type FalaRank = { id: number; texto: string; nivel: string; chave: string; votos: number }
```

### CSV
`GET admin.exportar` com `{tipo}` = `metricas | cadastros | questoes | retencao` (links em `admin.exportar`). Só números agregados, UTF-8 com BOM, uma linha de auditoria (`exportar_csv`) por download. Use `<a href download>`, não Inertia.

### Aluno (site): reportar erro
Ver `docs/contrato-paginas.md`, resposta de `POST /tentativas/{t}/respostas` (`report`) e `POST /questoes/{questao}/reports`.

## Revisão de segurança (`NEZUKO`): decisões e riscos aceitos

- **Novo login limpa o 2FA da sessão:** `EstadoDaSessao::iniciar()` apaga `dois_fatores_em` e `reconfirmado_em`; entrar como B depois de A sempre cai em `/dois-fatores`.
- **Assinatura da sessão:** o HMAC cobre a senha e o `confirmado_em` do 2FA; `senha` e `2fa-reset` derrubam as sessões abertas.
- **Senha e códigos nunca vão para a sessão:** `dontFlash` em `bootstrap/app.php`; `SESSION_ENCRYPT=true` no template de produção.
- **Auditoria imutável no banco:** trigger `admin_auditoria_imutavel` (única mudança permitida: `admin_id` virar null quando o admin é apagado).
- **CSV:** células iniciadas por `= + - @`, tab ou retorno de carro ganham apóstrofo.
- **E-mail revelado:** o flash carrega `id` e `tipo` do alvo e só aparece na ficha correspondente.
- **Limite de 20 reports/dia:** contagem e gravação sob `lockForUpdate` na linha do aluno.
- **Risco aceito, bloqueio de login por e-mail:** o limite de 5 tentativas/min por e-mail permite que um terceiro segure o login de um admin conhecido (DoS de bloqueio). Aceito: são poucos admins, o bloqueio dura 1 minuto, e a alternativa (sem limite por e-mail) facilita força bruta.
