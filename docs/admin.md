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
   - **Busca:** por apelido ou e-mail exato/prefixo, com filtros de status, série e "tem responsável".
   - **Lista:** apelido, e-mail mascarado, série, idade, status, cadastro, última atividade (data, sem hora) e nº de respostas.
   - **Ficha:** os mesmos dados, mais responsáveis vinculados (e-mail mascarado e parentesco), pedidos e convites pendentes, desempenho resumido por matéria e o botão "revelar e-mail".
3. **Responsáveis:**
   - **Busca:** por e-mail.
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
