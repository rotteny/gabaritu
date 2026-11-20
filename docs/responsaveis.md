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
9. **Aluno que faz 18 anos.** Os vínculos continuam (o aluno pode encerrar pela Conta, opção "encerrar acompanhamento", só quando tiver 18 anos ou mais). Fica fora do MVP se complicar; registre como pendência.
10. **O aluno sabe que é acompanhado.** A Conta do aluno mostra quem são os responsáveis (nome, parentesco e e-mail mascarado), os convites pendentes e o que eles veem.
    - **O aluno pede a inclusão de um responsável.** Ele informa o e-mail e o parentesco e o sistema envia um convite do tipo `coresponsavel_aluno`. O responsável entra pelo mesmo fluxo de convite: cria a conta ou faz login, declara e autoriza. Também vale para o aluno ativo, com limite de 3 convites por dia e no máximo 5 responsáveis por aluno. O aluno pode cancelar o convite pendente, mas não pode remover um responsável ativo: só o próprio responsável revoga o vínculo.
11. **Parentesco.** Todo vínculo tem `parentesco`: `pai`, `mae`, `avo` (avó ou avô), `tutor` (tutor ou guardião legal), `outro` (com texto livre curto: tio, padrasto…). O responsável confirma ou corrige o parentesco ao autorizar. A declaração passa a ser "sou responsável legal por este aluno (pai, mãe, avó/avô, tutor ou guardião com a guarda)". Quem pede o convite (aluno ou responsável) informa o parentesco esperado.
12. **Retenção.**
    - Aluno que fica `aguardando_responsavel` por mais de **30 dias** é apagado por um comando agendado (`gabaritu:limpar-pendentes`), com aviso por e-mail 3 dias antes.
    - Convites vencidos são apagados.
    - Conta de responsável sem nenhum aluno por 30 dias é apagada pelo mesmo comando.
13. **Papéis.** A tabela `users` ganha `tipo` (`aluno` ou `responsavel`). O responsável não estuda: só acessa o painel e a própria conta. Aluno não acessa `/responsavel`. Um e-mail tem uma conta só.
14. **Segurança.** Toda rota do responsável checa se o vínculo é ativo e se o aluno é dele (IDOR), com rate limit em convite, reenvio e aceite. A resposta do convite não revela se o e-mail tem conta.

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
| `GET /responsavel/convite/{token}` | `responsavel/Convite.vue` | `convite: { aluno: { apelido, serie }, email, tipo: 'autorizacao'|'coresponsavel'|'coresponsavel_aluno'|'transferencia', parentesco, parentesco_outro, expira_em }`, `tem_conta: boolean`, `logado_como_destinatario: boolean`; token inválido ou vencido: página de erro clara |
| `POST /responsavel/convite/{token}` | redirect → `/responsavel` | sem conta: `nome, senha, senha_confirmation, ano_nascimento, parentesco, parentesco_outro?, declara_responsavel, aceite_termos`; com conta (logado): `parentesco, parentesco_outro?, declara_responsavel, aceite_termos` |
| `POST /responsavel/convite/{token}/recusar` | redirect → `/` | — |
| `GET /responsavel` | `responsavel/Painel.vue` | `alunos: [{ id, apelido, serie, idade, status, teto_zoeira_responsavel, responsaveis: [{ nome, parentesco, email_mascarado, sou_eu }], convites_pendentes: [{ email_mascarado, tipo, expira_em }] }]`, `fala` |
| `GET /responsavel/alunos/{aluno}` | `responsavel/Aluno.vue` | `aluno` (como acima) + o mesmo payload de desempenho de `/desempenho` |
| `PATCH /responsavel/alunos/{aluno}/zoeira` | redirect | `teto: 'fofo'|'sarc'|'sem'` (`sem` = sem limite do responsável) |
| `POST /responsavel/alunos/{aluno}/convites` | redirect | `email`, `parentesco`, `parentesco_outro?`, `tipo: 'coresponsavel'|'transferencia'` |
| `DELETE /responsavel/alunos/{aluno}/vinculo` | redirect | revogar o próprio vínculo (bloqueado se for o único: 422 com mensagem clara) |
| `DELETE /responsavel/alunos/{aluno}` | redirect | `senha` → apaga o aluno |
| `GET /responsavel/conta` | `responsavel/Conta.vue` | dados do responsável, trocar senha, apagar a própria conta (bloqueado enquanto for o único responsável de algum aluno) |
| `GET /conta` (aluno, existente) | `Conta.vue` | + `responsaveis: [{ nome, parentesco, email_mascarado }]`, `convites_responsavel: [{ id, email_mascarado, parentesco, expira_em }]`, `pode_convidar: boolean` |
| `POST /conta/responsaveis` | redirect | `email`, `parentesco`, `parentesco_outro?` (throttle 3/dia, máx. 5 responsáveis) |
| `DELETE /conta/responsaveis/convites/{convite}` | redirect | cancela um convite pendente do próprio aluno |
| `GET /termos` | `Termos.vue` | pública, inclusive com o cadastro fechado |

**Login:** responsável cai em `/responsavel`; aluno pendente cai em `/aguardando-responsavel`.

**Prop compartilhada:** `auth.user` ganha `tipo` e `status`.

**E-mails** (Markdown, com o tom leve do Peixoto, mas a informação clara primeiro):
- convite de autorização;
- convite de co-responsável;
- convite de transferência;
- aluno autorizado (para o aluno);
- autorização recusada (para o aluno);
- aviso de remoção por pendência.

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
