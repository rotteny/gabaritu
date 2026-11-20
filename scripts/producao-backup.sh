#!/usr/bin/env bash
# Backup diário do banco do Gabaritu (pg_dump -Fc), local, com rotação de 7 dias. Rodar NO servidor.
#   producao-backup.sh run       gera /data/backups/gabaritu/<timestamp>.dump (+ .sha256) e aplica a retenção
#   producao-backup.sh install   copia para /usr/local/sbin/gabaritu-backup e cria /etc/cron.d/gabaritu-backup (03:40)
# Cópia em /usr/local/sbin: o cron roda como root e o checkout do repo é gravável pelo usuário de deploy.
set -euo pipefail

BACKUP_ROOT="${BACKUP_ROOT:-/data/backups/gabaritu}"
# Pasta só com um marcador vazio (<timestamp>.ok) montada read-only no app: o painel de saúde lê a data dele
# sem ver os dumps (BACKUP_ROOT é 0700 root).
STATUS_DIR="${STATUS_DIR:-/data/backups/gabaritu-status}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"
COMPOSE_PROJECT=gabaritu
INSTALLED_PATH=/usr/local/sbin/gabaritu-backup
CRON_FILE=/etc/cron.d/gabaritu-backup
LOG_FILE=/var/log/gabaritu-backup.log

log() { echo "[gabaritu-backup $(date -Is)] $*"; }
die() { log "ERRO: $*" >&2; exit 1; }
require_root() { [[ $EUID -eq 0 ]] || die "rode como root (sudo)"; }

db_container() {
  docker ps --filter "label=com.docker.compose.project=$COMPOSE_PROJECT" \
    --filter "label=com.docker.compose.service=db" --format '{{.Names}}' | head -n1
}

dump_database() { # $1 = arquivo de destino
  local container; container="$(db_container)"
  [[ -n "$container" ]] || die "container db do projeto $COMPOSE_PROJECT não encontrado"
  docker exec "$container" sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$1.partial"
  [[ -s "$1.partial" ]] || die "dump vazio"
  mv "$1.partial" "$1"
  (cd "$(dirname "$1")" && sha256sum "$(basename "$1")" > "$(basename "$1").sha256")
}

prune_old() {
  find "$BACKUP_ROOT" -maxdepth 1 -type f \( -name '*.dump' -o -name '*.dump.sha256' \) -mtime +"$RETENTION_DAYS" -delete
}

mark_success() {
  install -d -m 755 "$STATUS_DIR"
  find "$STATUS_DIR" -maxdepth 1 -type f -name '*.ok' -delete
  touch "$STATUS_DIR/$(date +%Y%m%d-%H%M%S).ok"
  chmod 644 "$STATUS_DIR"/*.ok
}

run() {
  require_root
  install -d -m 700 "$BACKUP_ROOT"
  local target; target="$BACKUP_ROOT/$(date +%Y%m%d-%H%M%S).dump"
  dump_database "$target"
  prune_old
  mark_success
  log "ok: $target ($(du -h "$target" | cut -f1))"
}

install_job() {
  require_root
  install -m 755 "${BASH_SOURCE[0]}" "$INSTALLED_PATH"
  echo "40 3 * * * root $INSTALLED_PATH run >> $LOG_FILE 2>&1" > "$CRON_FILE"
  chmod 644 "$CRON_FILE"
  log "instalado: $INSTALLED_PATH e $CRON_FILE"
}

case "${1:-}" in
  run) run ;;
  install) install_job ;;
  *) echo "uso: $0 {run|install}"; exit 2 ;;
esac
