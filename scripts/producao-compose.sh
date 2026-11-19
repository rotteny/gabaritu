#!/usr/bin/env bash
# Wrapper do docker compose de produção: fixa o projeto e a pasta do .env. Uso: scripts/producao-compose.sh ps | logs -f app | ...
set -euo pipefail
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
exec docker compose -p gabaritu --project-directory "$APP_DIR" -f "$APP_DIR/infra/producao/compose.yaml" "$@"
