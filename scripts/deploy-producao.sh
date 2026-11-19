#!/usr/bin/env bash
# Deploy de produção, rodado NO servidor (45.178.177.3) pelo workflow deploy.yml ou à mão.
# Uso: scripts/deploy-producao.sh [ref]   (padrão: origin/main)
# Fluxo: atualizar código -> build (tag = sha) -> migrar com a imagem NOVA -> só então virar o `current`.
# Se o build ou a migration falharem, o que está no ar continua intacto.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REF="${1:-origin/main}"
SERVICES=(app gabaritu-web)
IMAGES=(gabaritu-app gabaritu-web)
KEEP_SHA_IMAGES=3

compose() { "$APP_DIR/scripts/producao-compose.sh" "$@"; }
log() { echo "[deploy-producao] $*"; }

validate_ref() { [[ "$REF" =~ ^[A-Za-z0-9._/-]+$ ]] || { log "ref inválido: $REF"; exit 1; }; }

update_code() {
  cd "$APP_DIR"
  git fetch --prune origin
  git reset --hard "$REF"
  log "código em $(git rev-parse --short=12 HEAD)"
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

main() {
  validate_ref
  update_code
  [[ -f "$APP_DIR/.env" ]] || "$APP_DIR/scripts/producao-init-env.sh"

  local sha; sha="$(git -C "$APP_DIR" rev-parse --short=12 HEAD)"
  export APP_TAG="$sha"

  log "build $sha"
  compose build
  compose up -d --wait db

  log "migrate com a imagem nova"
  compose run --rm -T --no-deps app php artisan migrate --force

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
