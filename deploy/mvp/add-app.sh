#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Adds one MVP to the shared server (run server-setup.sh first):
#
#   sudo bash add-app.sh <app> <domain> [--no-tls]
#   sudo bash add-app.sh holidayhub holidayhub.unclecanvas.com
#
# Creates, for that MVP only:
#   /var/www/<app>/{releases,current,shared}   releases + shared .env and storage
#   a PostgreSQL database and user              (password written to shared/.env)
#   a PHP-FPM pool                               /run/php/<app>.sock, on demand
#   an nginx site and HTTPS certificate         (DNS must already point here)
#   a queue worker (supervisor) and the scheduler (cron)
#
# Never overwrites an existing shared/.env. Safe to run again.
# Options via environment: QUEUES (default "default"), ACME_EMAIL.
# ---------------------------------------------------------------------------
set -euo pipefail

APP="${1:-}"; DOMAIN="${2:-}"; TLS=true
[ "${3:-}" = "--no-tls" ] && TLS=false
DEPLOY_USER="${DEPLOY_USER:-ubuntu}"
PHP_VERSION="${PHP_VERSION:-8.4}"
QUEUES="${QUEUES:-default}"
ACME_EMAIL="${ACME_EMAIL:-admin@${DOMAIN#*.}}"

log() { printf '\033[0;36m[%s]\033[0m %s\n' "$APP" "$*"; }
svc() { if [ -d /run/systemd/system ]; then systemctl "$1" "$2"; else service "$2" "$1"; fi; }

if [ "$(id -u)" -ne 0 ]; then echo "Run as root: sudo bash $0 <app> <domain>" >&2; exit 1; fi
if ! [[ "$APP" =~ ^[a-z][a-z0-9-]{1,30}$ ]] || [ -z "$DOMAIN" ]; then
    echo "Usage: sudo bash $0 <app: lowercase letters, digits, -> <domain> [--no-tls]" >&2; exit 1
fi

ROOT="/var/www/$APP"
DB="${APP//-/_}"
SOCK="/run/php/${APP}.sock"

log "Folders…"
mkdir -p "$ROOT"/{releases,shared/storage/app/public,shared/storage/app/private,shared/storage/framework/cache/data,shared/storage/framework/sessions,shared/storage/framework/views,shared/storage/logs}
# The app runs as the deploy user and writes uploads, caches and logs here;
# nginx (www-data) reads public files through the group.
chown -R "$DEPLOY_USER":www-data "$ROOT"
chmod 755 "$ROOT"
chmod -R ug+rwX,o-w "$ROOT/shared" "$ROOT/releases"

log "Database…"
if ! sudo -u postgres psql -Atc "select 1 from pg_roles where rolname='$DB'" | grep -q 1; then
    DB_PASSWORD="$(openssl rand -hex 24)"
    sudo -u postgres psql -qc "create role \"$DB\" login password '$DB_PASSWORD'"
    sudo -u postgres psql -qc "create database \"$DB\" owner \"$DB\""
else
    DB_PASSWORD=""
fi

# Only this app's own user may connect to its database (Postgres lets every
# user connect to every database by default).
sudo -u postgres psql -qc "revoke connect on database \"$DB\" from public"
sudo -u postgres psql -qc "grant connect on database \"$DB\" to \"$DB\""

if [ ! -f "$ROOT/shared/.env" ]; then
    log "Writing shared/.env…"
    [ -z "$DB_PASSWORD" ] && { echo "Database user exists but shared/.env is missing; set DB_PASSWORD by hand." >&2; DB_PASSWORD=CHANGE_ME; }
    SCHEME=$([ "$TLS" = true ] && echo https || echo http)
    cat > "$ROOT/shared/.env" <<ENV
APP_NAME="${APP}"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:$(openssl rand -base64 32)
APP_URL=${SCHEME}://${DOMAIN}

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${DB}
DB_USERNAME=${DB}
DB_PASSWORD=${DB_PASSWORD}

# Redis is shared by every MVP: the prefix keeps each app's keys apart.
REDIS_HOST=127.0.0.1
REDIS_PREFIX=${DB}_
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_COOKIE=${DB}_session
SESSION_SECURE_COOKIE=$([ "$TLS" = true ] && echo true || echo false)

FILESYSTEM_DISK=public
MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@${DOMAIN}
MAIL_FROM_NAME="${APP}"

# First admin, created by the demo seed (password: 16+ characters).
ADMIN_EMAIL=
ADMIN_PASSWORD=

BOOKING_TEST_MODE=true
ENV
    chown "$DEPLOY_USER":www-data "$ROOT/shared/.env"
    chmod 640 "$ROOT/shared/.env"
fi

log "PHP-FPM pool…"
cat > "/etc/php/${PHP_VERSION}/fpm/pool.d/${APP}.conf" <<POOL
[${APP}]
user = ${DEPLOY_USER}
group = www-data
listen = ${SOCK}
listen.owner = www-data
listen.group = www-data
pm = ondemand
pm.max_children = 6
pm.process_idle_timeout = 20s
pm.max_requests = 500
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 256M
php_admin_value[post_max_size] = 260M
php_admin_value[max_execution_time] = 300
php_admin_value[error_log] = ${ROOT}/shared/storage/logs/php-fpm.log
POOL
"php-fpm${PHP_VERSION}" -t
svc reload "php${PHP_VERSION}-fpm"
# Make sure the pool is actually listening before nginx is pointed at it.
for _ in 1 2 3 4 5; do [ -S "$SOCK" ] && break; sleep 1; done
[ -S "$SOCK" ] || svc restart "php${PHP_VERSION}-fpm"
for _ in 1 2 3 4 5; do [ -S "$SOCK" ] && break; sleep 1; done
[ -S "$SOCK" ] || { echo "PHP-FPM pool ${APP} did not start; see /var/log/php${PHP_VERSION}-fpm.log" >&2; exit 1; }

log "nginx site…"
cat > "/etc/nginx/sites-available/${APP}" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${ROOT}/current/public;
    index index.php;
    charset utf-8;

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location ~ ^/(build|css/filament|js/filament|fonts/filament)/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:${SOCK};
        # The real path of the release, so a deploy switches over cleanly.
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known) { deny all; }

    access_log /var/log/nginx/${APP}.access.log;
    error_log /var/log/nginx/${APP}.error.log warn;
}
NGINX
ln -sfn "/etc/nginx/sites-available/${APP}" "/etc/nginx/sites-enabled/${APP}"
nginx -t
svc reload nginx

log "Queue worker and scheduler…"
cat > "/etc/supervisor/conf.d/${APP}-worker.conf" <<SUP
[program:${APP}-worker]
command=/usr/bin/php ${ROOT}/current/artisan queue:work --queue=${QUEUES} --sleep=3 --tries=3 --max-time=3600
directory=${ROOT}/current
user=${DEPLOY_USER}
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=60
stdout_logfile=${ROOT}/shared/storage/logs/worker.log
redirect_stderr=true
SUP
supervisorctl reread >/dev/null && supervisorctl update >/dev/null || true

echo "* * * * * ${DEPLOY_USER} [ -f ${ROOT}/current/artisan ] && /usr/bin/php ${ROOT}/current/artisan schedule:run >> /dev/null 2>&1" \
    > "/etc/cron.d/${APP}-scheduler"

if [ "$TLS" = true ]; then
    log "HTTPS certificate for ${DOMAIN}…"
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$ACME_EMAIL" --redirect
fi

log "Ready. Set ADMIN_EMAIL/ADMIN_PASSWORD in ${ROOT}/shared/.env, then deploy from GitHub Actions."
