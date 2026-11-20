# Responsável legal (ECA Digital e LGPD)

Este documento é a fonte única de verdade entre o backend (`ESCANOR`) e as páginas (`BULMA`) para o fluxo de responsável. Ele complementa o `docs/contrato-paginas.md`. Se precisar mudar algo, mude aqui e avise.

## Por quê
Pelo art. 24 do ECA Digital (Lei 15.211/2025, em vigor desde 17/03/2026), a conta de quem tem até 16 anos precisa estar ligada à conta de um responsável. O dono decidiu ir além: **todo aluno menor de 18 anos** só usa a plataforma depois que um responsável maior de idade se cadastra e autoriza.

## Regras de negócio
1. **Quem precisa de responsável.** Todo aluno com idade < 18 (idade = ano atual − ano de nascimento − 1, a mesma regra de hoje). Aluno de 18 anos ou mais segue o cadastro atual, sem responsável. A idade mínima continua 13.
2. **Cadastro do aluno.** No cadastro, o aluno menor informa o **e-mail do responsável**, que tem de ser diferente do dele. A conta nasce com o status `aguardando_responsavel`.
3. **Convite.** O sistema envia ao responsável um e-mail com um link assinado e de uso único, válido por **7 dias**, que diz quem pediu (apelido e série do aluno) e o que vai acontecer. O token é guardado como hash.
4. **Aluno aguardando.** Depois do login, o aluno com status `aguardando_responsavel` só acessa a tela **Aguardando responsável**, além de `/conta` (só pra apagar a conta e sair) e das páginas públicas. Nessa tela ele pode:
   - reenviar o convite, no máximo 3 por dia;
   - corrigir o e-mail do responsável, o que invalida o convite anterior;
   - sair.

   Qualquer outra rota de aluno redireciona para essa tela.
5. **Cadastro e autorização do responsável.** O link do convite leva a `/responsavel/convite/{token}`.
   - **Se ele ainda não tem conta:** cria a conta de responsável com nome, e-mail (o do convite, travado), senha, ano de nascimento (precisa ter 18 anos ou mais) e duas declarações:
     - "sou pai, mãe ou responsável legal por este aluno";
     - "li e aceito os Termos e a Política de Privacidade".
   - **Se já tem conta** (é responsável por outro aluno): faz login e só confirma as duas declarações.
   - **Autorizar ou recusar.** Ao autorizar, o vínculo fica ativo e o aluno passa a `ativo`, e o aluno recebe um e-mail avisando. Ao recusar, o aluno continua aguardando e vê a mensagem "seu responsável recusou"; pode corrigir o e-mail.
   - **O que se grava em cada aceite:** data e hora, versão dos termos, IP e as declarações.
6. **Painel do responsável** (`/responsavel`). Mostra a lista de alunos vinculados, com status e, de cada um:
   - **Desempenho por matéria, só leitura:** nota, assuntos e diagnóstico, o mesmo dado de `/desempenho`.
   - **Teto de zoeira:** o responsável pode baixar o teto (`fofo` ou `sarc`) do aluno. O teto efetivo é o menor entre o da idade e o do responsável.
   - **Revogar a autorização:** o aluno volta a `aguardando_responsavel` se ficar sem nenhum responsável ativo. Não pode revogar se for o único responsável; nesse caso ele transfere ou apaga a conta do aluno.
   - **Convidar outro responsável:** um co-responsável, com o mesmo fluxo de convite.
   - **Transferir a responsabilidade:** envia o convite para outro e-mail. Quando o novo aceita, o vínculo do antigo é encerrado. Enquanto isso, o antigo continua responsável.
   - **Apagar a conta do aluno:** pede a senha do responsável e apaga tudo do aluno.
7. **Ao menos 1 responsável.** O aluno menor sempre tem pelo menos 1 vínculo ativo para ficar `ativo`. Nenhuma ação deixa um aluno `ativo` com zero vínculos.
8. **Um responsável, vários alunos.** Uma conta de responsável pode estar ligada a N alunos, e um aluno a N responsáveis.
9. **Aluno que faz 18 anos.** Os vínculos continuam até o aluno decidir. Na Conta, "Encerrar acompanhamento" (só 18+) apaga todos os vínculos, invalida convites pendentes e avisa os responsáveis por e-mail; não há restauração. Se o aluno 18+ contestar um responsável, o vínculo suspenso só pode ser removido, nunca restaurado (422 `vinculo`).
10. **O aluno sabe que é acompanhado.** A Conta do aluno mostra quem são os responsáveis (nome, parentesco e e-mail mascarado), os convites pendentes e o que eles veem.
    - **O aluno pede a inclusão de um responsável.** Ele informa o e-mail e o parentesco e o sistema envia um convite do tipo `coresponsavel_aluno`. O responsável entra pelo mesmo fluxo de convite: cria a conta ou faz login, declara e autoriza. Também vale para o aluno ativo, com limite de 3 convites por dia e no máximo 5 responsáveis por aluno. O aluno pode cancelar o convite pendente, mas não pode remover um responsável ativo: só o próprio responsável revoga o vínculo.
11. **Parentesco.** Todo vínculo tem `parentesco`: `pai`, `mae`, `avo` (avó ou avô), `tutor` (tutor ou guardião legal), `outro` (com texto livre curto: tio, padrasto…). O responsável confirma ou corrige o parentesco ao autorizar. A declaração passa a ser "sou responsável legal por este aluno (pai, mãe, avó/avô, tutor ou guardião com a guarda)". Quem pede o convite (aluno ou responsável) informa o parentesco esperado.
12. **Retenção.**
    - Aluno que fica `aguardando_responsavel` por mais de **30 dias** é apagado por um comando agendado (`gabaritu:limpar-pendentes`), com aviso por e-mail 3 dias antes.
    - Convites vencidos são apagados. A prova do aceite e da aprovação fica em `aceites_responsavel` (sem chave estrangeira para o convite; some junto com a conta do aluno).
    - A limpeza roda com `withoutOverlapping` e reconfere o critério com a linha travada, dentro da transação, antes de apagar.
    - Conta de responsável sem nenhum aluno por 30 dias é apagada pelo mesmo comando.
13. **Papéis.** A tabela `users` ganha `tipo` (`aluno` ou `responsavel`). O responsável não estuda: só acessa o painel e a própria conta. Aluno não acessa `/responsavel`. Um e-mail tem uma conta só.
14. **Segurança.** Toda rota do responsável checa se o vínculo é ativo e se o aluno é dele (IDOR), com rate limit em convite, reenvio e aceite. A resposta do convite não revela se o e-mail tem conta.
    - **E-mail de responsável.** Um aluno menor nunca usa como responsável o próprio e-mail nem o e-mail de uma conta com `tipo=aluno`. Vale no cadastro (`email_responsavel`), na correção da espera, em `POST /conta/responsaveis` e nos convites de co-responsável e transferência: 422 com a mesma mensagem nos dois casos, sem dizer qual: "Use o e-mail de um adulto responsável por você. Esse e-mail não pode ser usado como responsável." A comparação normaliza (trim, minúsculas, sem `+tag` em Gmail). O aceite também barra quem está logado como aluno (mesmo com 18 anos ou mais) e visitante cujo e-mail já é de conta de aluno.
    - **Trade-off conhecido:** barrar e-mail de conta de aluno confirma que aquele e-mail é de um aluno. É decisão do dono; o risco é limitado pelos rate limits de cadastro e de convite.

## Dados mínimos do responsável (decisão do dono)
O responsável informa só o necessário para identificá-lo e responsabilizá-lo. Os dados guardados são:
- **Nome:** identifica quem autorizou.
- **E-mail:** login, contato e prova de que o convite chegou a ele.
- **Ano de nascimento:** confirma que ele tem 18 anos ou mais.
- **Parentesco.**
- **Registro do aceite:** data e hora, IP, versão dos termos e as declarações, como prova da autorização.

**Proibido pedir:** CPF, RG, telefone, endereço, data de nascimento completa, foto ou documento. Se no futuro um advogado ou uma regulamentação da ANPD exigir verificação mais forte da idade ou da guarda, isso entra como mudança versionada nos Termos. Por ora fica em `docs/lgpd/` como "verificar com advogado".

## Rotas e páginas (Inertia)
| Rota | Página | Props / campos |
|---|---|---|
| `POST /cadastro` | (existente) | + `email_responsavel`, `parentesco_responsavel`, `parentesco_outro?` (obrigatório se a idade for < 18; diferente do `email`). Aluno menor: redirect para `/aguardando-responsavel`. |
| `GET /aguardando-responsavel` | `aluno/AguardandoResponsavel.vue` | `email_responsavel_mascarado`, `convite_expira_em`, `recusado: boolean`, `reenvios_restantes`, `fala` |
| `POST /aguardando-responsavel/reenviar` | redirect | throttle |
| `PATCH /aguardando-responsavel/email` | redirect | `email_responsavel` |
| `GET /responsavel/convite/{token}` | `responsavel/Convite.vue` | `token`, `convite: { aluno: { apelido, serie }, email, tipo: 'autorizacao'|'coresponsavel'|'coresponsavel_aluno'|'transferencia', parentesco, parentesco_outro, expira_em } | null`, `vencido: boolean`, `tem_conta: boolean`, `logado_como_destinatario: boolean`. Token inválido, usado ou vencido: a mesma página, com `convite: null` (`vencido: true` só quando o prazo passou). Aluno logado no aceite: 422 com a mensagem da regra 14. |
| `POST /responsavel/convite/{token}` | redirect → `/responsavel` | sem conta: `nome, senha, senha_confirmation, ano_nascimento, parentesco, parentesco_outro?, declara_responsavel, aceite_termos`; com conta (logado): `parentesco, parentesco_outro?, declara_responsavel, aceite_termos` |
| `POST /responsavel/convite/{token}/recusar` | redirect → `/` | — |
| `GET /responsavel` | `responsavel/Painel.vue` | `alunos: [{ id, apelido, serie, avatar, idade, status, teto_zoeira_responsavel, notas: [{ slug, nome, nota }], responsaveis: [{ nome, parentesco, parentesco_outro, email_mascarado, status: 'ativo'|'suspenso', autorizado_em, sou_eu }], convites_pendentes: [{ id, email_mascarado, tipo, expira_em, pode_cancelar }], pedidos_pendentes: [{ id, solicitante: { nome, parentesco, parentesco_outro, email_mascarado }, aluno_aceitou, expira_em }] }]`, `pedidos_enviados: [{ id, email_mascarado, parentesco, parentesco_outro, expira_em }]`, `fala` |
| `GET /responsavel/alunos/{aluno}` | `responsavel/Aluno.vue` | `aluno` (como acima) + o mesmo payload de desempenho de `/desempenho` |
| `PATCH /responsavel/alunos/{aluno}/zoeira` | redirect | `teto: 'fofo'|'sarc'|'sem'` (`sem` = sem limite do responsável) |
| `POST /responsavel/alunos/{aluno}/convites` | redirect | `email`, `parentesco`, `parentesco_outro?`, `tipo: 'coresponsavel'|'transferencia'` |
| `DELETE /responsavel/alunos/{aluno}/vinculo` | redirect | revogar o próprio vínculo (bloqueado se for o único: 422 com mensagem clara) |
| `DELETE /responsavel/alunos/{aluno}` | redirect | `senha` → apaga o aluno |
| `GET /responsavel/conta` | `responsavel/Conta.vue` | `responsavel: { nome, email, ano_nascimento }`, `alunos_unicos: string[]` (apelidos dos alunos em que ele é o único responsável). `PUT /responsavel/conta/senha` troca a senha (`senha_atual`, `senha`, `senha_confirmation`); `DELETE /responsavel/conta` (`senha`) apaga a conta, 422 enquanto for o único responsável de algum aluno. |
| `GET /conta` (aluno, existente) | `Conta.vue` | + `pode_encerrar_acompanhamento: boolean` (18+ com ao menos um vínculo), `perfil.avatar`, `avatares: string[]`, `responsaveis: [{ id, nome, parentesco, parentesco_outro, email_mascarado, status, autorizado_em }]`, `convites_responsavel: [{ id, email_mascarado, parentesco, parentesco_outro, expira_em }]`, `pedidos_vinculo: [{ id, solicitante: { nome, parentesco, parentesco_outro, email_mascarado }, expira_em, aguarda_responsavel, aceito_por_mim }]`, `pode_convidar: boolean` |
| `POST /conta/responsaveis` | redirect | `email`, `parentesco`, `parentesco_outro?` (throttle 3/dia, máx. 5 responsáveis) |
| `DELETE /conta/responsaveis/convites/{convite}` | redirect | cancela um convite pendente do próprio aluno |
| `POST /conta/acompanhamento/encerrar` | redirect | só aluno 18+ (422 na chave `acompanhamento` se menor): apaga todos os vínculos, invalida convites pendentes e avisa os responsáveis por e-mail. Sem restauração. |
| `PATCH /conta/perfil` | redirect | `apelido`, `serie` e `avatar` são `sometimes`: pode mandar só o avatar. |
| `DELETE /responsavel/alunos/{aluno}/convites/{convite}` | redirect | veto: qualquer responsável ativo cancela um convite `coresponsavel_aluno` pendente (404 fora disso) |
| `GET /termos` | `Termos.vue` | pública, inclusive com o cadastro fechado |

**Login:** responsável cai em `/responsavel`; aluno pendente cai em `/aguardando-responsavel`. O acesso dos dois tipos é separado (ver "Acesso separado").

**Prop compartilhada:** `auth.user` ganha `tipo`, `status` e `avatar`.

**E-mails** (Markdown com `@component('mail::message')` padrão, sem tema próprio; tom leve do Peixoto, mas a informação clara primeiro):
- convite de autorização;
- convite de co-responsável;
- convite de transferência;
- aluno autorizado (para o aluno);
- autorização recusada (para o aluno);
- aviso de remoção por pendência;
- confirmação de cadastro (aluno): adulto ("sua conta está pronta") ou menor ("falta a autorização", com o e-mail do responsável mascarado e os 7 dias);
- convite de autorização / `coresponsavel_aluno`: um só texto, com "Criar conta e autorizar" (e-mail sem conta de responsável) ou "Entrar e autorizar" (com conta, via `/responsavel/entrar?redirect=`); `coresponsavel` e `transferencia` mantêm texto próprio;
- autorização confirmada (para o responsável: resumo, painel, como revogar e registro do aceite com data, hora e versão dos termos);
- pedido do responsável ao aluno (sem conta: link `/cadastro?convite=`; com conta: link para a Conta);
- aviso aos responsáveis atuais sobre um pedido de vínculo;
- aviso aos responsáveis atuais sobre o convite de co-responsável feito pelo aluno (nome, parentesco e e-mail mascarado; só aviso e veto, sem aprovação);
- acompanhamento encerrado (aluno 18+, para cada responsável);
- vínculo contestado pelo aluno (aos outros responsáveis e ao contato do Gabaritu).

## Páginas legais
- **`/termos` (nova):**
  - o que é o serviço e a idade mínima;
  - a regra do responsável e o motivo (ECA Digital);
  - deveres e direitos do aluno e do responsável;
  - conduta;
  - como encerrar, transferir ou revogar;
  - limitação (projeto gratuito, sem garantia de aprovação);
  - contato;
  - versão e data.
- **`/privacidade` (atualizar):**
  - os dados do responsável (nome, e-mail, ano de nascimento, aceites, IP no aceite);
  - quem vê o quê (o responsável vê o desempenho do aluno);
  - bases legais, incluindo o melhor interesse do adolescente;
  - IP e navegador na sessão e nos logs;
  - backup por até 7 dias depois da exclusão;
  - retenção de pendentes (30 dias);
  - direitos do titular e do responsável;
  - canal de contato.

  Versionar a política: subir `versao_politica` e a data.
- **Rodapé:** Termos, Privacidade e Quem somos.
- **`docs/lgpd/registro-de-operacoes.md` e `retencao.md`:** atualizar com as novas operações.

Não inventar fato jurídico: o que for duvidoso entra em `docs/lgpd/` como "verificar com advogado" e não vai pro texto público.

## Acesso separado (aluno e responsável)
- `/entrar` aceita só `tipo=aluno`; `/responsavel/entrar` (`responsavel/Entrar.vue`, props `status`) aceita só `tipo=responsavel`. Credencial certa no login errado cai na mesma mensagem genérica ("E-mail ou senha incorretos."). O link "É responsável? Entre por aqui" (e o inverso) é só de tela.
- Recuperação de senha separada: `/responsavel/esqueci-senha` (`responsavel/EsqueciSenha.vue`) e `/responsavel/redefinir-senha/{token}` (`responsavel/RedefinirSenha.vue`, props `email`, `token`; `POST` com `token`, `email`, `senha`, `senha_confirmation`). O link do e-mail leva ao fluxo do tipo da conta; token de um fluxo não funciona no outro. A resposta é igual para e-mail existente ou não.
- `?redirect=` nas duas telas de login só vale para caminho interno (uma barra no início); URL externa é ignorada (open redirect).
- Rotas por tipo: aluno em `/responsavel/*` e responsável nas rotas de aluno (`/inicio`, `/conta`, `/aguardando-responsavel`...) recebem 404; visitante é mandado para o login do tipo da rota. Uma conta por e-mail, sem distinção de maiúsculas.
- Mesmo guard, sessão regenerada no login; logout volta para a landing.

## Pedido iniciado pelo responsável
- **`POST /responsavel/pedidos`** (botão "Adicionar aluno"): `email`, `parentesco`, `parentesco_outro?`, `declara_responsavel` (aceito). Resposta sempre igual (a busca do e-mail, a criação e o envio rodam depois da resposta, no mesmo caminho para qualquer e-mail; só o limite diário roda antes): "Se existir um aluno com esse e-mail, ele receberá o pedido." (flash `status`), exista conta ou não. Máx. 5 pedidos por dia por responsável (422 na chave `email`). Nunca gera pedido para o e-mail de outro responsável, para o próprio ou para aluno com 18+. O aceite do responsável (IP, data e hora, versão dos termos) fica gravado no pedido.
- **E-mail sem conta** (`pedido_responsavel`, aluno nulo): e-mail "<nome> quer ser seu responsável no Gabaritu" com link `/cadastro?convite=<token>`. `GET /cadastro` ganha `convite_responsavel: { token, responsavel: { nome }, email, parentesco, parentesco_outro, expira_em } | null`. `POST /cadastro` aceita `convite` e `confirma_responsavel`: com confirmação (e menor de 18), o aluno nasce `ativo`, vinculado, sem passar pela espera. Sem confirmar, o pedido é recusado e o fluxo normal pede `email_responsavel`. Com 18+: 422 na chave `convite` ("Maiores de idade não precisam de responsável."). O e-mail do cadastro tem de ser o convidado (422 na chave `convite`).
- **E-mail de aluno já cadastrado** (`pedido_vinculo`): o aluno recebe e-mail e vê o pedido em `Conta.pedidos_vinculo`; `POST /conta/pedidos/{id}/aceitar` e `/recusar` (valem também para o aluno pendente).
- **Proteção contra adulto estranho:** com responsável ativo, o pedido (`exige_aprovacao`) só vira vínculo quando o aluno aceita **e** um responsável atual aprova (`POST /responsavel/pedidos/{id}/aprovar` ou `/recusar`, em qualquer ordem); todos os atuais recebem e-mail com nome, parentesco e e-mail mascarado de quem pediu. Sem responsável ativo, basta o aluno aceitar. O solicitante cancela o próprio pedido em `DELETE /responsavel/pedidos/{id}`. O pedido vale 7 dias e não funciona como convite de responsável.
- Ao concluir, o solicitante recebe "você autorizou <apelido>" e o vínculo guarda o aceite feito no pedido.

## Contestação do aluno ("Essa pessoa não é meu responsável")
- `POST /conta/responsaveis/{responsavel}/contestar` (aluno): o vínculo fica `suspenso` e o acesso daquele responsável cai na hora (404 nas rotas do aluno). Os outros responsáveis e o contato do Gabaritu recebem e-mail.
- Quem sobrou vê o vínculo com `status: 'suspenso'` e resolve: `DELETE /responsavel/alunos/{aluno}/suspensos/{responsavel}` (confirmar remoção) ou `POST .../restaurar`. Sem ninguém ativo, o aluno volta para `aguardando_responsavel` (na espera, `PATCH /aguardando-responsavel/email` aceita também `parentesco_responsavel`/`parentesco_outro` quando ainda não há parentesco guardado).
- Rotas por tipo e IDOR: tudo isso responde 404 fora do vínculo ativo.

## Avatar do aluno
Lista fixa em `config('gabaritu.avatares')` (12 bichos marinhos), sem upload de foto, de propósito (não guardamos imagem de menor). `avatar` é opcional no cadastro (sorteado se faltar) e editável em `PATCH /conta/perfil` (422 fora da lista). Props: `auth.user.avatar`, `perfil.avatar` e `avatares` na Conta, `avatar` de cada aluno no painel e em `/responsavel/alunos/{aluno}`.

## Versão dos textos
`config('gabaritu.termos_versao')` e `privacidade_versao` valem `2026-11-22` (mesmo valor). Mudou o texto, mude a versão.

## Detalhes de implementação (mudanças em relação ao desenho inicial)
- `responsaveis[].parentesco_outro` e `autorizado_em` acrescentados; no painel, `status` do vínculo.
- Convite inválido usa `responsavel/Convite` com `convite: null`, sem página de erro própria.
- Responsável sem aluno há 30 dias é apagado (conta com só vínculos suspensos também conta como sem aluno).
- `GET /aguardando-responsavel`: `email_responsavel_mascarado` pode ser `null` (aluno que voltou à espera depois de contestar).
- Revisão de segurança (`NEZUKO`): convite de co-responsável ou de transferência só vale enquanto quem emitiu tem vínculo ativo (revogado, contestado, transferido ou apagado, o convite morre). O pedido do responsável reavalia `exige_aprovacao` na conclusão: se o aluno ganhou responsável depois do pedido, um deles passa a aprovar e recebe o aviso. Quem o aluno contestou não manda pedido novo. Revogar, contestar, restaurar, remover suspenso e apagar responsável travam a linha do aluno (`FOR UPDATE`), então duas revogações simultâneas não zeram os responsáveis. Aluno que apaga a própria conta marca o responsável sem aluno (limpeza de 30 dias).
