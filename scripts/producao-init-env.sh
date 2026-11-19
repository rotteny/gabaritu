#!/usr/bin/env bash
# Cria o .env de produção com chaves NOVAS (aleatórias, geradas aqui, nunca impressas). Idempotente: recusa sobrescrever.
set -euo pipefail
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TEMPLATE="$APP_DIR/infra/producao/env.producao.template"
TARGET="$APP_DIR/.env"

[[ ! -e "$TARGET" ]] || { echo ".env já existe em $TARGET; nada a fazer."; exit 0; }

umask 077
sed \
  -e "s|@GERAR_APP_KEY@|base64:$(openssl rand -base64 32)|" \
  -e "s|@GERAR_DB_PASSWORD@|$(openssl rand -hex 24)|" \
  "$TEMPLATE" > "$TARGET"
chmod 600 "$TARGET"
echo ".env criado em $TARGET (0600)."
