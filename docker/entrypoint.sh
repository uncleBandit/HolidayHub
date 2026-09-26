#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# HolidayHub container entrypoint
# ---------------------------------------------------------------------------
set -euo pipefail

log() { printf '\033[0;36m[entrypoint]\033[0m %s\n' "$*"; }
err() { printf '\033[0;31m[entrypoint]\033[0m %s\n' "$*" >&2; }

# --- Fix writable paths (only possible while still root) --------------------
fix_permissions() {
    if [ "$(id -u)" -eq 0 ]; then
        log "Fixing storage/bootstrap cache permissions…"
        mkdir -p \
            storage/framework/cache/data \
            storage/framework/sessions \
            storage/framework/views \
            storage/logs \
            bootstrap/cache
        chown -R www-data:www-data storage bootstrap/cache
        chmod -R ug+rwX storage bootstrap/cache
    fi
}

# --- Wait for PostgreSQL ----------------------------------------------------
# Compose's `depends_on: service_healthy` already covers the common case; this
# is the fallback for `docker run` and for slow cold starts.
wait_for_db() {
    local tries="${DB_WAIT_TRIES:-30}"
    log "Waiting for database (${tries} attempts)…"

    local i=1
    while [ "$i" -le "$tries" ]; do
        if php -r '
            $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s",
                getenv("DB_HOST") ?: "127.0.0.1",
                getenv("DB_PORT") ?: "5432",
                getenv("DB_DATABASE") ?: "postgres"
            );
            try {
                new PDO($dsn, getenv("DB_USERNAME") ?: "postgres", getenv("DB_PASSWORD") ?: "");
                exit(0);
            } catch (Throwable $e) { exit(1); }
        ' 2>/dev/null; then
            log "Database is up."
            return 0
        fi
        i=$((i + 1))
        sleep 2
    done

    err "Database did not become available in time."
    return 1
}

# --- Migrations -------------------------------------------------------------
# Deliberately opt-in. With N replicas starting at once, an automatic
# `migrate` in the entrypoint causes N concurrent migration runs. Run this as a
# single release job instead:
#     docker compose run --rm app php artisan migrate --force
run_migrations() {
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        log "Running migrations…"
        php artisan migrate --force --no-interaction
    fi
}

# --- Optimise for production ------------------------------------------------
warm_caches() {
    if [ "${APP_ENV:-production}" = "production" ]; then
        log "Caching configuration, routes and views…"
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
    fi
}

# --- Bootstrap dev dependencies --------------------------------------------
# In development the source tree is bind-mounted, which shadows the image's
# vendor/ directory with an empty one. Recreate it on first boot.
bootstrap_vendor() {
    if [ "${APP_ENV:-production}" = "production" ]; then
        return 0
    fi
    if [ -f vendor/autoload.php ]; then
        return 0
    fi
    if ! command -v composer >/dev/null 2>&1; then
        err "vendor/ is missing and composer is unavailable."
        return 1
    fi

    log "vendor/ missing — installing Composer dependencies (first boot only)…"
    composer install --no-interaction --prefer-dist
    composer dump-autoload
}

# --- Lifecycle shortcuts ----------------------------------------------------
# `queue` and `scheduler` services reuse this image with a different command.
if [ "${1:-}" = "queue" ]; then
    shift
    fix_permissions
    bootstrap_vendor
    wait_for_db
    log "Starting queue worker…"
    exec php artisan queue:work \
        --queue="${QUEUE_NAME:-default},bookings,payments,notifications" \
        --sleep=2 --tries=3 --max-time=3600 \
        --max-exceptions=5 "${@:-}"

elif [ "${1:-}" = "scheduler" ]; then
    shift
    fix_permissions
    bootstrap_vendor
    wait_for_db
    log "Starting scheduler…"
    exec php artisan schedule:work "${@:-}"

elif [ "${1:-}" = "migrate" ]; then
    shift
    fix_permissions
    bootstrap_vendor
    wait_for_db
    log "Running migrations…"
    exec php artisan migrate --force --no-interaction "${@:-}"

elif [ "${1:-}" = "test" ]; then
    shift
    fix_permissions
    bootstrap_vendor
    wait_for_db
    log "Running test suite…"
    exec php artisan test "${@:-}"

else
    fix_permissions
    bootstrap_vendor
    wait_for_db
    run_migrations
    warm_caches
    exec "$@"
fi
