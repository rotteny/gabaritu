#!/usr/bin/env bash
# Deploy de produção, rodado NO servidor (45.178.177.3) pelo workflow deploy.yml ou à mão.
# Uso: scripts/deploy-producao.sh [ref]   (padrão: origin/main)
# Fluxo: atualizar código -> build (tag = sha) -> pg_dump -> migrar e importar conteúdo com a imagem NOVA -> só então virar o `current`.
# Se o build, a migration ou a importação falharem, o que está no ar continua intacto.
# O script se re-executa depois do `git reset`: sem isso, o bash rodaria a versão ANTERIOR do próprio arquivo
# (o `command=` do authorized_keys chama este arquivo do clone) e passos novos só valeriam no deploy seguinte.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REF="${1:-origin/main}"
SERVICES=(app gabaritu-web)
IMAGES=(gabaritu-app gabaritu-web)
KEEP_SHA_IMAGES=3
PREDEPLOY_BACKUP_DIR="${PREDEPLOY_BACKUP_DIR:-$HOME/backups/gabaritu-predeploy}"
KEEP_PREDEPLOY_DUMPS=5

compose() { "$APP_DIR/scripts/producao-compose.sh" "$@"; }
log() { echo "[deploy-producao] $*"; }

validate_ref() { [[ "$REF" =~ ^[A-Za-z0-9._/-]+$ ]] || { log "ref inválido: $REF"; exit 1; }; }

update_code() {
  cd "$APP_DIR"
  git fetch --prune origin
  git reset --hard "$REF"
  log "código em $(git rev-parse --short=12 HEAD)"
}

# Dump antes do migrate, feito pelo usuário de deploy (o backup diário em /data é só do root).
backup_before_migrate() {
  install -d -m 700 "$PREDEPLOY_BACKUP_DIR"
  local target; target="$PREDEPLOY_BACKUP_DIR/$(date +%Y%m%d-%H%M%S)-$sha.dump"
  compose exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$target.partial"
  [[ -s "$target.partial" ]] || { log "dump vazio, abortando"; rm -f "$target.partial"; exit 1; }
  mv "$target.partial" "$target"
  ls -1t "$PREDEPLOY_BACKUP_DIR"/*.dump | tail -n +$((KEEP_PREDEPLOY_DUMPS + 1)) | xargs -r rm -f
  log "backup: $target"
}

tag_all() { # $1 origem, $2 destino
  for image in "${IMAGES[@]}"; do docker tag "$image:$1" "$image:$2"; done
}

keep_previous() {
  if docker image inspect gabaritu-app:current >/dev/null 2>&1; then tag_all current previous; fi
}

prune_old_sha_images() {
  docker image ls gabaritu-app --format '{{.Tag}}' | grep -E '^[0-9a-f]{12}$' | tail -n +$((KEEP_SHA_IMAGES + 1)) |
    while read -r old; do docker image rm "${IMAGES[@]/%/:$old}" >/dev/null 2>&1 || true; done
}

reexec_with_updated_code() {
  [[ -z "${DEPLOY_REEXECUTADO:-}" ]] || return 0
  validate_ref
  update_code
  export DEPLOY_REEXECUTADO=1
  exec "$APP_DIR/scripts/deploy-producao.sh" "$REF"
}

main() {
  reexec_with_updated_code
  validate_ref
  [[ -f "$APP_DIR/.env" ]] || "$APP_DIR/scripts/producao-init-env.sh"

  local sha; sha="$(git -C "$APP_DIR" rev-parse --short=12 HEAD)"
  export APP_TAG="$sha"

  log "build $sha"
  compose build
  compose up -d --wait db

  log "backup do banco antes do migrate"
  backup_before_migrate

  log "migrate com a imagem nova"
  compose run --rm -T --no-deps app php artisan migrate --force

  log "importar conteúdo com a imagem nova (idempotente)"
  compose run --rm -T --no-deps app php artisan gabaritu:importar-conteudo

  keep_previous
  tag_all "$sha" current
  unset APP_TAG

  log "subindo serviços"
  compose up -d --force-recreate --wait "${SERVICES[@]}"
  compose exec -T gabaritu-web wget -q -O /dev/null http://127.0.0.1/up
  prune_old_sha_images
  log "ok: $sha no ar"
}

# Na mesma linha: o bash lê tudo antes de rodar, então o `git reset` no meio do deploy não corrompe a leitura deste arquivo.
main; exit $?
