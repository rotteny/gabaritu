# Operação da produção (modo "em breve")

Ambiente: `https://gabaritu.gorilash.com.br`. Servidor: `45.178.177.3` (usuário de deploy `gorillas`), o mesmo do gts-acca e outros projetos, cada um em stack isolada. Sem segredos neste arquivo: eles ficam só no `.env` do servidor e nos secrets do GitHub.

**Modo "em breve":** `GABARITU_CADASTRO_ABERTO=false`. Só `/` (página do Peixoto com o e-mail de contato) e `/robots.txt` respondem; o resto devolve 404 e as páginas saem com noindex. Não abrir antes da revisão de segurança.

## Como é montado

Projeto compose isolado `gabaritu` (`infra/producao/compose.yaml`), sem Redis (sessão, cache e fila em banco) e sem worker/scheduler (nada roda em fila com o cadastro fechado; ao abrir, adicionar `queue:work` e `schedule:work`).

| Serviço        | Imagem                       | Função                                                                                      |
| -------------- | ---------------------------- | ------------------------------------------------------------------------------------------- |
| `db`           | `postgres:18.6-trixie`       | banco próprio (`gabaritu`), volume `pgdata`, 384 MB                                         |
| `app`          | `gabaritu-app` (PHP 8.4 FPM) | php-fpm, 256 MB                                                                             |
| `gabaritu-web` | `gabaritu-web` (nginx)       | serve `public/` e fala FastCGI com o `app`; alias `gabaritu-web` na rede `laradock_backend` |

- Borda: o nginx do Laradock termina o TLS e faz proxy para `gabaritu-web` (vhost `/app/laradock/nginx/sites/gabaritu.conf`, fonte em `infra/producao/nginx/edge/`). Cabeçalhos de segurança ficam lá.
- Proxy confiável: `TRUSTED_PROXIES=172.16.0.0/12` (bridges do Docker). Sem isso o Laravel geraria URLs `http`.
- E-mail: `MAIL_MAILER=log` (serviço transacional ainda não escolhido). A caixa de contato `gabaritu@gorilash.com.br` é só para receber (cPanel da HostGator, conta `gorilash`, 1 GB); a senha fica em `~/servers/gorilash/gabaritu-mail.env` na máquina do dono, fora de qualquer repo.

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

`scripts/deploy-producao.sh`: atualiza o código, builda as imagens (tag = sha), sobe o `db`, roda `migrate --force` com a imagem nova (se falhar, o que está no ar não muda), vira a tag `current`, recria `app` e `gabaritu-web` e confere `/up`. Guarda `previous` e as 3 últimas imagens por sha. Rollback rápido: `docker tag gabaritu-app:previous gabaritu-app:current` (idem `gabaritu-web`) e `scripts/producao-compose.sh up -d --force-recreate app gabaritu-web`; não desfaz migrations.

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
- Manual: `sudo gabaritu-backup run`.
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

## Abrir o cadastro (depois da revisão de segurança)

Trocar `GABARITU_CADASTRO_ABERTO=true` no `.env` do servidor, escolher o serviço de e-mail transacional (`MAIL_MAILER`), subir `queue:work`/`schedule:work` e rodar um deploy (o container relê o `.env` ao recriar).
