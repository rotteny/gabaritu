# syntax=docker/dockerfile:1.7
# Produção do Gabaritu. Contexto de build = raiz do repositório:
#   docker build -f infra/producao/Dockerfile --target app -t gabaritu-app:local .
#   docker build -f infra/producao/Dockerfile --target web -t gabaritu-web:local .
# app = php-fpm (e comandos artisan). web = nginx só com public/.
ARG PHP_VERSION=8.4
ARG NGINX_VERSION=1.27

FROM php:${PHP_VERSION}-fpm-alpine AS php-base
COPY --from=ghcr.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl bcmath mbstring opcache \
    && rm -f /usr/local/bin/install-php-extensions
WORKDIR /var/www/html

FROM php-base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY artisan ./
COPY bootstrap ./bootstrap
COPY app ./app
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY resources ./resources
COPY lang ./lang
COPY conteudo ./conteudo
COPY public ./public
RUN mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# O plugin Wayfinder do Vite roda `php artisan wayfinder:generate`: o estágio de front precisa de PHP + vendor.
FROM vendor AS front
RUN apk add --no-cache nodejs npm
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci
COPY vite.config.ts tsconfig.json ./
# APP_KEY descartável: só para o artisan subir durante o build; não vai para a imagem final.
RUN APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= npm run build

FROM php-base AS app
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'memory_limit=256M\nexpose_php=0\nopcache.validate_timestamps=0\n' > "$PHP_INI_DIR/conf.d/zz-gabaritu.ini"
COPY --from=vendor --chown=www-data:www-data /var/www/html ./
COPY --from=front --chown=www-data:www-data /var/www/html/public/build ./public/build
RUN chown -R www-data:www-data storage bootstrap/cache
COPY --chmod=0755 infra/producao/entrypoint.sh /usr/local/bin/gabaritu-entrypoint
USER www-data
EXPOSE 9000
ENTRYPOINT ["gabaritu-entrypoint"]
CMD ["php-fpm"]

FROM nginx:${NGINX_VERSION}-alpine AS web
RUN rm -f /etc/nginx/conf.d/default.conf
COPY infra/producao/nginx/web.conf /etc/nginx/conf.d/web.conf
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 80
