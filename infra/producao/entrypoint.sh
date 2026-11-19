#!/bin/sh
# Cache de config/eventos/views por container (o env vem do compose, não de .env no disco).
set -eu
cd /var/www/html
php artisan config:cache --no-interaction >/dev/null
php artisan event:cache --no-interaction >/dev/null
php artisan view:cache --no-interaction >/dev/null
exec "$@"
