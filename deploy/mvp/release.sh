#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Activates an uploaded release on the shared MVP server. Run by the deploy
# workflow, as the deploy user, from inside the release it activates:
#
#   bash /var/www/<app>/releases/<id>/deploy/mvp/release.sh <app> <id> [--reset-demo]
#
#   1. links the shared .env and storage into the release
#   2. migrates, and seeds the demo data if the database is empty
#      (--reset-demo: rebuild the database and reseed a clean demo)
#   3. caches config, routes, views and events
#   4. switches /var/www/<app>/current to the release in one step
#   5. restarts the queue worker, then checks the site answers on /up,
#      switching back to the previous release if it does not
#   6. keeps the last 5 releases
# ---------------------------------------------------------------------------
set -euo pipefail

APP="$1"; ID="$2"; RESET="${3:-}"
ROOT="/var/www/$APP"
REL="$ROOT/releases/$ID"
PHP_VERSION="${PHP_VERSION:-8.4}"
log() { printf '\033[0;36m[release %s]\033[0m %s\n' "$ID" "$*"; }

[ -d "$REL" ] || { echo "No release at $REL" >&2; exit 1; }
[ -f "$ROOT/shared/.env" ] || { echo "Missing $ROOT/shared/.env (run add-app.sh)" >&2; exit 1; }

log "Linking shared .env and storage…"
ln -sfn "$ROOT/shared/.env" "$REL/.env"
rm -rf "$REL/storage"
ln -sfn "$ROOT/shared/storage" "$REL/storage"
ln -sfn ../storage/app/public "$REL/public/storage"
mkdir -p "$REL/bootstrap/cache"

cd "$REL"
artisan() { php artisan "$@" --no-interaction; }

# Every MVP provides `php artisan demo:seed` (seed the demo data when the
# database is empty; --fresh to rebuild it). Apps without it just migrate.
has_demo_seed() { php artisan list --raw 2>/dev/null | grep -q '^demo:seed '; }

if [ "$RESET" = "--reset-demo" ]; then
    has_demo_seed || { echo "--reset-demo needs a demo:seed command in this app." >&2; exit 1; }
    log "Resetting the demo database…"
    artisan demo:seed --fresh
else
    log "Migrating…"
    artisan migrate --force
    if has_demo_seed; then
        artisan demo:seed
    else
        log "No demo:seed command in this app; skipping demo data."
    fi
fi

log "Caching…"
artisan optimize

PREVIOUS="$(readlink -f "$ROOT/current" 2>/dev/null || true)"

log "Switching current → $ID…"
ln -sfn "$REL" "$ROOT/current.next"
mv -Tf "$ROOT/current.next" "$ROOT/current"

restart() {
    # Restart through supervisor: it stops the worker gracefully (SIGTERM lets
    # the current job finish) and starts it on the new code. It also starts a
    # worker supervisor gave up on, e.g. before the very first release existed.
    if ! sudo -n supervisorctl restart "${APP}-worker" >/dev/null 2>&1; then
        php "$ROOT/current/artisan" queue:restart --no-interaction >/dev/null || true
    fi
    sudo -n systemctl reload "php${PHP_VERSION}-fpm" 2>/dev/null || true
}
restart

APP_URL="$(grep -E '^APP_URL=' "$ROOT/shared/.env" | cut -d= -f2- | tr -d '"')"
SCHEME="${APP_URL%%://*}"; HOST="${APP_URL#*://}"; HOST="${HOST%%/*}"
PORT=$([ "$SCHEME" = https ] && echo 443 || echo 80)

log "Checking ${APP_URL}/up…"
healthy=false
for _ in 1 2 3 4 5 6; do
    if curl -fsS --max-time 10 --resolve "${HOST}:${PORT}:127.0.0.1" "${APP_URL}/up" >/dev/null; then
        healthy=true; break
    fi
    sleep 3
done

if [ "$healthy" != true ]; then
    if [ -n "$PREVIOUS" ] && [ "$PREVIOUS" != "$REL" ]; then
        echo "The new release did not answer on /up; switching back to $(basename "$PREVIOUS")." >&2
        ln -sfn "$PREVIOUS" "$ROOT/current.next"
        mv -Tf "$ROOT/current.next" "$ROOT/current"
        restart
    else
        echo "The release did not answer on /up and there is no previous release to go back to." >&2
    fi
    exit 1
fi

log "Live. Keeping the last 5 releases…"
cd "$ROOT/releases"
ls -1dt -- */ 2>/dev/null | tail -n +6 | xargs -r rm -rf --
log "Done."
