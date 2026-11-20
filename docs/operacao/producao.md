# Operação da produção

Ambiente: `https://gabaritu.gorilash.com.br`. Servidor: `45.178.177.3` (usuário de deploy `gorillas`), o mesmo do gts-acca e outros projetos, cada um em stack isolada. Sem segredos neste arquivo: eles ficam só no `.env` do servidor e nos secrets do GitHub.

**Cadastro aberto desde 20/11/2026** (`GABARITU_CADASTRO_ABERTO=true`, após a revisão da `NEZUKO`). Para fechar de novo (modo "em breve": só `/` e `/robots.txt` respondem, o resto 404 e noindex), voltar a `false` no `.env`, recriar os containers e rodar `config:cache`.

## Como é montado

Projeto compose isolado `gabaritu` (`infra/producao/compose.yaml`), sem Redis (sessão, cache e fila em banco), sem worker (as notificações e o e-mail de boas-vindas saem síncronos; se algo passar a usar `ShouldQueue`, adicionar `queue:work`) e com `scheduler` (`schedule:work`).

| Serviço        | Imagem                       | Função                                                                                      |
| -------------- | ---------------------------- | ------------------------------------------------------------------------------------------- |
| `db`           | `postgres:18.6-trixie`       | banco próprio (`gabaritu`), volume `pgdata`, 384 MB                                         |
| `app`          | `gabaritu-app` (PHP 8.4 FPM) | php-fpm, 256 MB                                                                             |
| `scheduler`    | `gabaritu-app` (mesma imagem e `.env`) | `php artisan schedule:work`, 128 MB; roda `gabaritu:limpar-pendentes` |
| `gabaritu-web` | `gabaritu-web` (nginx)       | serve `public/` e fala FastCGI com o `app`; alias `gabaritu-web` na rede `laradock_backend` |

- Borda: o nginx do Laradock termina o TLS e faz proxy para `gabaritu-web` (vhost `/app/laradock/nginx/sites/gabaritu.conf`, fonte em `infra/producao/nginx/edge/`). Cabeçalhos de segurança ficam lá.
- Proxy confiável: `TRUSTED_PROXIES=172.16.0.0/12` (bridges do Docker). Sem isso o Laravel geraria URLs `http`.
- E-mail: SMTP da HostGator (`MAIL_MAILER=smtp`, `MAIL_SCHEME=smtps`, `mail.gorilash.com.br:465`, remetente e usuário `gabaritu@gorilash.com.br`, nome "Gabaritu"). A senha fica só no `.env` do servidor e em `~/servers/gorilash/gabaritu-mail.env` na máquina do dono. As notificações (recuperação de senha) saem síncronas, não vão para a fila, então não há worker; se um e-mail passar a usar `ShouldQueue`, subir `queue:work`. SPF e DKIM do domínio existem; DMARC está em `p=none`. Teste: `Mail::raw` dentro do `app` para a própria caixa.

## Arquivos no servidor

| O quê                                                                        | Onde                                                                                                |
| ---------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Código (clone público, branch `main`)                                        | `/app/www/gabaritu`                                                                                 |
| `.env` (0600, chaves geradas no servidor por `scripts/producao-init-env.sh`) | `/app/www/gabaritu/.env`                                                                            |
| Desafio ACME                                                                 | `/app/www/gabaritu-acme`                                                                            |
| Vhost de borda                                                               | `/app/laradock/nginx/sites/gabaritu.conf`                                                           |
| Certificado (cópia para o nginx)                                             | `/app/laradock/nginx/ssl/gabaritu/` (hook `/etc/letsencrypt/renewal-hooks/deploy/copy-to-nginx.sh`) |
| Backups                                                                      | `/data/backups/gabaritu/`                                                                           |

Não troque o `APP_KEY` depois de haver dados (sessões e cookies cifrados deixam de valer).

## Deploy (redeploy)

Automático: push na `main` roda `.github/workflows/deploy.yml`: o CI (`ci.yml`) e, **só se passar**, o deploy por SSH. Também dá para disparar em Actions > deploy > Run workflow.

O acesso usa uma chave dedicada (secrets `DEPLOY_SSH_KEY` e `DEPLOY_SSH_KNOWN_HOSTS`, no environment `producao`). No `~gorillas/.ssh/authorized_keys` ela está com `restrict,command="cd /app/www/gabaritu && exec scripts/deploy-producao.sh origin/main"`: só roda o deploy. Para trocar: gere outra chave, troque a linha no `authorized_keys` e rode `gh secret set DEPLOY_SSH_KEY --env producao`.

À mão, no servidor:

```bash
cd /app/www/gabaritu && scripts/deploy-producao.sh            # origin/main
cd /app/www/gabaritu && scripts/deploy-producao.sh <sha|ref>  # outra ref (rollback por código)
```

`scripts/deploy-producao.sh`: atualiza o código, builda as imagens (tag = sha), sobe o `db`, faz `pg_dump` (passo abaixo), roda `migrate --force` e depois `gabaritu:importar-conteudo` com a imagem nova (se qualquer um falhar, o que está no ar não muda), vira a tag `current`, recria `app` e `gabaritu-web` e confere `/up`. Guarda `previous` e as 3 últimas imagens por sha. Rollback rápido: `docker tag gabaritu-app:previous gabaritu-app:current` (idem `gabaritu-web`) e `scripts/producao-compose.sh up -d --force-recreate app gabaritu-web`; não desfaz migrations (para isso, restaurar o dump pré-deploy, ver "Backup e restauração").

**Auto-atualização do script:** o `command=` do `authorized_keys` roda o `scripts/deploy-producao.sh` do clone no servidor (não há cópia separada). Como o deploy faz `git reset` no meio, o script se re-executa logo após atualizar o código (`DEPLOY_REEXECUTADO`), então cada deploy usa a versão do commit que está sendo implantado. Antes disso (até o deploy de `ece0138`) o bash rodava a versão anterior do arquivo e passos novos só apareciam no deploy seguinte; foi assim que backup e importação sumiram do log. Confira no log: devem aparecer "backup do banco antes do migrate" e "importar conteúdo com a imagem nova".

**Backup pré-migrate:** todo deploy grava `~gorillas/backups/gabaritu-predeploy/<data>-<sha>.dump` (`pg_dump -Fc`, 0700, últimos 5; dump vazio aborta o deploy). É separado do backup diário em `/data/backups/gabaritu/`, que é do root.

**Conteúdo:** `conteudo/` (matérias, questões, falas) e `lang/` vão na imagem do `app` (ver `infra/producao/Dockerfile`). O importador é idempotente: reexecutar não duplica. Só entra conteúdo que estiver no commit; para mudar o banco de questões, commit na `main` e deploy.

**Imagens das questões:** o importador grava `public/conteudo/img/<hash>.<ext>` (ignorado pelo git). Como o importador roda num container descartável (`run --rm`) e quem serve é o `gabaritu-web`, a pasta é o volume nomeado `imagens`, montado em escrita no `app` e somente leitura no `gabaritu-web`; o nginx interno serve `/conteudo/img/` com cache imutável e CSP `sandbox`. A CSP da borda (`img-src 'self' data:`) já permite essas imagens, sem mudança. A imagem do `app` precisa da extensão `gd` (o importador recodifica PNG/WebP para tirar metadados).

**Questões do ENEM (INEP):** o material não vai para o git. Copie os JSON e imagens para `/app/www/gabaritu/conteudo-externo/enem/` (legível pelo uid do `www-data`, `chmod -R a+rX`). Se essa pasta existir, o deploy monta-a só no run do importador (`-v ...:/conteudo-externo/enem:ro`) e roda `gabaritu:importar-conteudo --fonte-externa=/conteudo-externo/enem`; sem a pasta, roda só o conteúdo autoral. A importação é idempotente pelo `id` e recusa tudo (rollback) se algum item ou imagem for inválido, o que aborta o deploy antes de virar o `current`. Para o aluno ver as questões, o `.env` precisa de `GABARITU_EXIBIR_INEP=true` (já no `env.producao.template`; num `.env` existente, adicione à mão). Só entram em questionário as questões classificadas (`materia` + `assunto`), em questionários `ENEM <ano> · <n>` de até 10 questões, por assunto e ano; anuladas, com `alt_pendente` ou com `revisao_juridica: remover` nunca aparecem. `gabaritu:inep --relatorio` mostra a contagem e `gabaritu:inep --remover` apaga tudo do INEP (questões, respostas, imagens e arquivos), gravando em `storage/logs/auditoria.log`; apagar respostas não recalcula a nota agregada por assunto.

**Estado em 20/11/2026:** material enviado por `rsync -a --chmod=D755,F644` (914 arquivos, 5 anos) para `/app/www/gabaritu/conteudo-externo/enem/` (untracked no clone do servidor; o deploy faz `reset`, sem `clean`, então não some); `GABARITU_EXIBIR_INEP=true` no `.env`; deploy de `6bf3090` importou 923 questões INEP (0 recusadas, 744 imagens no volume `imagens`). Todas com `jurídico: pendente` (a análise jurídica segue aberta). Smoke no ar: `/`, `/entrar`, `/termos` (seção "Questões do ENEM") e `/privacidade` 200; imagem de `/conteudo/img/` 200 `image/png`; questão com figura, fonte "ENEM 2021, INEP/MEC" e "Explicação em breve", sem violação de CSP; conta de teste apagada pela interface, sem resíduo. Backup manual antes: `~gorillas/backups/gabaritu-manual/`.

Reimportar à mão, no servidor (usa a imagem `current`):

```bash
cd /app/www/gabaritu
scripts/producao-compose.sh run --rm -T --no-deps app php artisan gabaritu:importar-conteudo
```

## Logs e estado

```bash
cd /app/www/gabaritu
scripts/producao-compose.sh ps
scripts/producao-compose.sh logs -f --tail 100 app
tail -f /app/laradock/logs/nginx/gabaritu-error.log
```

## Borda: DNS e certificado (uma vez)

1. DNS (zona `gorilash.com.br` na HostGator, PowerDNS com backend bind; **não** é Cloudflare). No servidor da HostGator, como root: backup da zona em `/root/gorilash.com.br.db.bak-<data>`, depois
   `whmapi1 addzonerecord domain=gorilash.com.br name=gabaritu.gorilash.com.br. type=A address=45.178.177.3 ttl=300 class=IN`
   (o nome precisa ser o FQDN completo com `.br.`). O PowerDNS só enxerga o registro novo depois de `pdns_control bind-reload-now gorilash.com.br`. Conferir: `dig +short gabaritu.gorilash.com.br @ns1.tikbot.com.br`.
2. No servidor da Gorila:
    ```bash
    sudo install -d -o gorillas -g gorillas /app/www/gabaritu-acme
    cd /app/www/gabaritu
    scripts/producao-edge.sh http    # vhost só com ACME + nginx -t + reload gracioso
    scripts/producao-edge.sh cert    # mapeia o lineage no hook e emite o certificado (HTTP-01)
    scripts/producao-edge.sh https   # vhost definitivo + nginx -t + reload + teste dos cabeçalhos
    ```
    Os hooks de `renewal-hooks/deploy/` só rodam em `certbot renew`, não em `certonly`: por isso o `cert` chama o hook à mão (com `RENEWED_LINEAGE`) logo após a emissão. Conferir a renovação sem renovar: `sudo certbot renew --cert-name gabaritu.gorilash.com.br --dry-run`.

## Backup e restauração

`scripts/producao-backup.sh`. Instalação, uma vez, no servidor: `sudo scripts/producao-backup.sh install` (copia para `/usr/local/sbin/gabaritu-backup` e cria `/etc/cron.d/gabaritu-backup`: todo dia 03:40, log em `/var/log/gabaritu-backup.log`). Depois de mudar o script, rode `install` de novo.

- Destino: `/data/backups/gabaritu/<AAAAMMDD-HHMMSS>.dump` (`pg_dump -Fc`, root, 0700) com `.sha256`. Retenção de 7 dias.
- Manual: `sudo gabaritu-backup run`. Os dumps pré-deploy (`~gorillas/backups/gabaritu-predeploy/`) restauram do mesmo jeito.
- **Cópia fora do servidor: pendente** (mesma situação dos outros projetos do servidor).

Restaurar:

```bash
cd /app/www/gabaritu
scripts/producao-compose.sh stop gabaritu-web app
DB=$(docker ps --filter label=com.docker.compose.project=gabaritu --filter label=com.docker.compose.service=db --format '{{.Names}}')
sudo docker exec $DB sh -c 'dropdb -U "$POSTGRES_USER" --if-exists "$POSTGRES_DB" && createdb -U "$POSTGRES_USER" "$POSTGRES_DB"'
sudo docker exec -i $DB sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --no-owner --exit-on-error' < /data/backups/gabaritu/<arquivo>.dump
scripts/producao-compose.sh up -d
```

## Abrir o cadastro

Feito em 20/11/2026. Passos, para repetir: `GABARITU_CADASTRO_ABERTO=true` no `.env`; `scripts/producao-compose.sh up -d --force-recreate --wait app scheduler gabaritu-web`; `scripts/producao-compose.sh exec -T app php artisan config:cache`. Efeito no `noindex`: o `<meta name="robots" content="noindex">` (só existe com o cadastro fechado) some de todas as páginas e o `robots.txt` passa a `Allow: /`. Não há `X-Robots-Tag` na borda. Páginas de aluno logado redirecionam para `/entrar`, então só as públicas são indexáveis.

Smoke test feito no ar: aluno adulto (2000) cadastrado, login, e-mail "Sua conta no Gabaritu está pronta" recebido (conferido por IMAP), 1 questão respondida, conta apagada por Conta > Apagar e banco sem nenhuma linha do id (users, tentativas, respostas, desempenhos, revisões, vínculos, convites, aceites, passkeys, sessões). Não testar o fluxo de menor com e-mail de terceiros.

## Agendador (scheduler)

Serviço `scheduler` no compose (`php artisan schedule:work`, `restart: unless-stopped`, mesma imagem e `.env` do `app`). Entra na lista de serviços recriados pelo `deploy-producao.sh` (antes ficava de fora e rodava código velho). Sobe junto do `app` (diário, 03:30 de Brasília): apaga alunos pendentes há mais de 30 dias (aviso 3 dias antes), convites vencidos e responsáveis sem aluno há 30 dias. Conferir depois do deploy (`schedule:list` deve mostrar `gabaritu:limpar-pendentes`): `scripts/producao-compose.sh ps scheduler` e `logs --tail 20 scheduler`.

## Logs sem token, retenção e CSP

A borda é o nginx do Laradock (não há Apache/cPanel na frente deste site).

- **Máscara:** `infra/producao/nginx/log-mascarado.conf` (`map` sobre `$request_uri` + `log_format gabaritu_mascarado`). Troca por `[oculto]` o token de `/responsavel/convite/`, `/redefinir-senha/` e `/responsavel/redefinir-senha/`, e os valores de `?convite=` e `email=`; o Referer sai como `-`. Na borda é instalado como `/app/laradock/nginx/sites/gabaritu-log.conf` pelo `scripts/producao-edge.sh https`; no nginx do container `gabaritu-web` entra pela imagem (`conf.d/`), no próximo deploy. Exemplo de linha (dados fictícios):
  `IP - - [20/Nov/2026:02:38:11 +0000] "GET /responsavel/convite/[oculto]?convite=[oculto]&email=[oculto] HTTP/2.0" 404 6778 "-" "curl/8.5.0"`
- **Limite:** o `error.log` do nginx pode registrar a URI crua em erros de upstream; não dá para mascarar. Fica só a retenção curta.
- **Retenção de 14 dias:** o logrotate do container nginx da borda rotaciona todo dia (`rotate 32`, regra de todos os sites). Para o Gabaritu, `scripts/producao-edge.sh logs` instala `/etc/cron.d/gabaritu-logs-retencao`, que apaga `gabaritu-*.log.*` com mais de 14 dias (acesso e erro). Logs do Docker: `max-size: 5m`, `max-file: 14` em todos os serviços do compose (teto por tamanho, o `json-file` não rotaciona por idade).
- **CSP (imposta desde 20/11/2026):** header `Content-Security-Policy` no vhost de borda (`gabaritu.https.conf`): tudo `'self'` (Vite build e fontes Titan One/Nunito, que o plugin Bunny baixa e serve de `/build/assets`), `style-src` com `'unsafe-inline'` (o `<style>` das fontes e `style=` do Vue), `img-src` com `data:`, `frame-ancestors 'none'`. Antes de impor, varredura em produção com Chromium headless, cadastro aberto, em Report-Only: `/`, `/quem-somos`, `/privacidade`, `/termos`, `/cadastro` (incluindo o envio do formulário), `/entrar`, `/responsavel/entrar`, `/inicio`, matéria, questão (com resposta) e `/conta` (apagar conta), sem nenhuma violação (console e evento `securitypolicyviolation`). Não varridos: telas do responsável logado e do aluno menor (exigem e-mail de terceiro); se aparecer violação, voltar o header para `Content-Security-Policy-Report-Only` e rodar `scripts/producao-edge.sh https`.
- **Aplicado no servidor em 19/11/2026:** vhost de borda (máscara + CSP) e o cron de retenção (`/etc/cron.d/gabaritu-logs-retencao`). Backup do vhost anterior: `/app/laradock/gabaritu.conf.bak-<data>`. Scheduler, máscara no nginx do container e `logging` do compose estão no ar desde o deploy de `748d9d2`.

## Painel admin

Detalhes de produto e segurança em `docs/admin.md`. Em produção:

- **Prefixo:** `GABARITU_ADMIN_PREFIXO` no `.env` (modelo em `infra/producao/env.producao.template`). O painel responde em `https://gabaritu.gorilash.com.br/<prefixo>/entrar`. Sem a variável, o prefixo é `admin` (adivinhável: sempre defina). Depois de mudar: recriar os containers e rodar `config:cache` e `route:cache` (o prefixo entra no cache de rotas). O prefixo aparece no log do nginx; não o compartilhe.
- **Criar o admin (uma vez, só o dono):** `scripts/producao-compose.sh exec app php artisan gabaritu:admin criar dono@exemplo.com`. A senha é pedida num prompt oculto (mínimo de 12 caracteres, maiúscula, minúscula e número); nunca passe por argumento. O comando exige terminal interativo (`exec` sem `-T`).
- **Primeiro login:** o painel obriga configurar o 2FA (QR para o app autenticador) e mostra 8 códigos de recuperação uma única vez. Guarde-os fora do servidor. Faça isso logo após criar a conta: até o 2FA ser confirmado, quem souber a senha poderia cadastrar o próprio autenticador.
- **Trocar a senha:** `gabaritu:admin senha <email>` (derruba as sessões abertas).
- **Perdeu o celular e os códigos:** `gabaritu:admin 2fa-reset <email>`; no próximo login o painel pede novo QR e novos códigos.
- **Sessão:** cookie próprio, caminho do prefixo, 30 minutos sem uso; revelar e-mail pede o TOTP de novo a cada 15 minutos. Tudo que o admin abre ou revela fica em `admin_auditoria` (sem IP).
- **Métricas:** `metricas_diarias` só tem contadores por dia (sem IP, user-agent nem id). O conjunto de "alunos ativos únicos" vive no cache com TTL de 36 horas e some sozinho.
- **Saúde do sistema:** `GABARITU_BACKUP_DIR` (pasta dos backups visível ao container; sem ela a tela mostra "não configurado") e `GABARITU_VERSAO` (commit do deploy; o `deploy-producao.sh` ainda não a exporta). O "último tick" vem do job `heartbeat` do scheduler, então o serviço `scheduler` precisa estar de pé.
- **Reports de questão e contas apagadas:** `reports_questao` e `contas_apagadas_mes` entram na migration do deploy; contas apagadas só contam a partir daí.
