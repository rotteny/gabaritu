# Gabaritu

Estudo para o ENEM com personalidade. O **Peixoto**, um baiacu, zoa a resposta errada e depois explica onde você se enrolou. Código aberto, feito para estudante.

> Licenças: o **código** é MIT ([LICENSE](LICENSE)); as **questões, falas e arte** são CC BY-SA 4.0 ([LICENSE-CONTENT](LICENSE-CONTENT)).

Escopo do MVP: [docs/escopo-mvp.md](docs/escopo-mvp.md).

## Stack

Laravel 13, PHP 8.4, Vue 3 + Inertia, Vite, PostgreSQL 18, Pest 5.

## Subir local (Laradock)

Pré-requisitos: Laradock rodando (workspace, nginx, postgres, mailpit) com `~/workspace` montado em `/var/www`, e `127.0.0.1 gabaritu.local` no hosts.

1. Crie os bancos `gabaritu` e `gabaritu_test` no Postgres do Laradock.
2. Copie [docs/laradock-nginx.conf.example](docs/laradock-nginx.conf.example) para `laradock/nginx/sites/gabaritu.conf` e recarregue o nginx do Laradock.
3. No container (`docker exec -u laradock -w /var/www/gabaritu laradock-workspace-1 ...`):

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build
```

Abra http://gabaritu.local. E-mails saem no Mailpit (porta 8125 do host).

## Modo "em breve"

`GABARITU_CADASTRO_ABERTO` (padrão `false`, lida em `config/gabaritu.php`) controla a abertura:

- `false`: só a home "em breve" (com `noindex`) e `robots.txt` com `Disallow: /`; cadastro, login e o resto respondem 404.
- `true`: comportamento normal do starter. No `.env` local fica `true`.

`GABARITU_EMAIL_CONTATO` define o e-mail exibido na página "em breve".

## Testes

```bash
php artisan test        # roda só no banco gabaritu_test
vendor/bin/pint --test  # estilo PHP
npm run check           # lint/format do front
```
