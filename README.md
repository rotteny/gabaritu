# Gabaritu

Estudo para o ENEM com personalidade. O **Peixoto**, um baiacu, zoa a resposta errada e depois explica onde você se enrolou. Código aberto, feito para estudante.

> Licenças: o **código** é MIT ([LICENSE](LICENSE)); as **questões, falas e arte** são CC BY-SA 4.0 ([LICENSE-CONTENT](LICENSE-CONTENT)).

Escopo do MVP: [docs/escopo-mvp.md](docs/escopo-mvp.md).

## Stack

Laravel 13, PHP 8.4, Vue 3 + Inertia, Vite, PostgreSQL 18, Pest 5.

## Subir local (Laradock)

Pré-requisitos: Laradock rodando (workspace, nginx, postgres, mailpit) com `~/workspace` montado em `/var/www`, e `127.0.0.1 gabaritu.local` no hosts.

1. Crie os bancos `gabaritu` e `gabaritu_test` no Postgres do Laradock.
2. Copie `nginx/sites/gabaritu.conf` do exemplo do repo de infra para o Laradock e recarregue o nginx.
3. No container (`docker exec -u laradock -w /var/www/gabaritu laradock-workspace-1 ...`):

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build
```

Abra http://gabaritu.local. E-mails saem no Mailpit (porta 8125 do host).

## Testes

```bash
php artisan test        # roda só no banco gabaritu_test
vendor/bin/pint --test  # estilo PHP
npm run check           # lint/format do front
```
