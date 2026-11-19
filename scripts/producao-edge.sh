#!/usr/bin/env bash
# Borda (nginx do Laradock + certbot) do Gabaritu. Rodar NO servidor, DEPOIS que o DNS de gabaritu.gorilash.com.br
# apontar para 45.178.177.3. Três fases, nesta ordem:
#   scripts/producao-edge.sh http    # vhost só com ACME (nginx -t + reload gracioso do nginx compartilhado)
#   scripts/producao-edge.sh cert    # mapeia o lineage no hook de renovação e emite o certificado (HTTP-01)
#   scripts/producao-edge.sh https   # vhost definitivo com TLS e cabeçalhos de segurança (nginx -t + reload)
# Se `nginx -t` falhar, NADA é recarregado.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LARADOCK_DIR=/app/laradock
SITE_CONF="$LARADOCK_DIR/nginx/sites/gabaritu.conf"
EDGE_SRC="$APP_DIR/infra/producao/nginx/edge"
HOST_NAME=gabaritu.gorilash.com.br
HOOK=/etc/letsencrypt/renewal-hooks/deploy/copy-to-nginx.sh
ACME_ROOT=/app/www/gabaritu-acme

nginx_in_laradock() { (cd "$LARADOCK_DIR" && docker compose exec -T nginx nginx "$@"); }

install_vhost() { # $1 = http | https
  [[ ! -f "$SITE_CONF" ]] || cp -p "$SITE_CONF" "$SITE_CONF.bak-$(date +%Y%m%d-%H%M%S)"
  install -m 644 "$EDGE_SRC/gabaritu.$1.conf" "$SITE_CONF"
  if ! nginx_in_laradock -t; then
    echo "nginx -t falhou: vhost NÃO recarregado (arquivo em $SITE_CONF; restaure o .bak ou remova)"; exit 1
  fi
  nginx_in_laradock -s reload
  echo "vhost $1 ativo"
}

map_lineage_in_hook() {
  grep -q "\[$HOST_NAME\]=gabaritu" "$HOOK" && return 0
  # Backup sem bit de execução: o certbot roda TODO executável de renewal-hooks/deploy/, inclusive cópias .bak.
  local backup; backup="$HOOK.bak-$(date +%Y%m%d-%H%M%S)"
  sudo cp -p "$HOOK" "$backup"; sudo chmod a-x "$backup"
  sudo sed -i "/^declare -A LINEAGE_TO_DIR=(/a\\  [$HOST_NAME]=gabaritu" "$HOOK"
  grep -q "\[$HOST_NAME\]=gabaritu" "$HOOK"
}

issue_certificate() {
  [[ -d "$ACME_ROOT" ]] || { echo "falta $ACME_ROOT (mkdir -p; ver docs/operacao/producao.md)"; exit 1; }
  map_lineage_in_hook
  # Hooks de renewal-hooks/deploy/ só rodam em `certbot renew`, nunca em `certonly`: chamamos o hook à mão.
  sudo certbot certonly --webroot -w "$ACME_ROOT" -d "$HOST_NAME" --cert-name "$HOST_NAME" \
    --key-type ecdsa --non-interactive --agree-tos
  sudo env RENEWED_LINEAGE="/etc/letsencrypt/live/$HOST_NAME" "$HOOK"
  test -s "$LARADOCK_DIR/nginx/ssl/gabaritu/fullchain.pem"
  echo "certificado emitido e copiado para o nginx"
}

smoke_https() {
  curl -fsS -o /dev/null -w 'https /up: %{http_code}\n' "https://$HOST_NAME/up"
  curl -sSI "https://$HOST_NAME/" | grep -iE '^(HTTP|strict-transport|x-frame|x-content-type|referrer|permissions|cross-origin)'
}

case "${1:-}" in
  http)  install_vhost http ;;
  cert)  issue_certificate ;;
  https) install_vhost https; smoke_https ;;
  *) echo "uso: $0 {http|cert|https}"; exit 2 ;;
esac
