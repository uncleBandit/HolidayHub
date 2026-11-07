#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# One-time setup of the shared MVP server (Lightsail or DigitalOcean, Ubuntu
# 22.04/24.04). Every MVP on the server shares what this installs:
#
#   nginx            one web server, a site per MVP (add-app.sh)
#   PHP 8.4 FPM      a pool per MVP, started on demand so idle demos use no RAM
#   PostgreSQL 17    one server, a database + user per MVP
#   Redis            one server, a key prefix per MVP
#   supervisor       each MVP's queue worker
#   certbot          HTTPS certificates per domain, renewed automatically
#   swap, nightly database backups, automatic security updates
#
#   sudo DEPLOY_USER=ubuntu bash server-setup.sh
#
# DEPLOY_USER is the account GitHub Actions logs in as and the apps run as:
# "ubuntu" on Lightsail; on DigitalOcean create a non-root user first.
# Safe to run again.
# ---------------------------------------------------------------------------
set -euo pipefail

DEPLOY_USER="${DEPLOY_USER:-ubuntu}"
PHP_VERSION="${PHP_VERSION:-8.4}"
PG_VERSION="${PG_VERSION:-17}"
export DEBIAN_FRONTEND=noninteractive

log() { printf '\033[0;36m[setup]\033[0m %s\n' "$*"; }

# systemctl on a real server; the service command inside a test container.
svc() {
    if [ -d /run/systemd/system ]; then systemctl "$1" "$2"; else service "$2" "$1"; fi
}

if [ "$(id -u)" -ne 0 ]; then echo "Run as root: sudo bash $0" >&2; exit 1; fi
id "$DEPLOY_USER" >/dev/null 2>&1 || { echo "User $DEPLOY_USER does not exist." >&2; exit 1; }

log "Base packages…"
apt-get update -y
apt-get install -y software-properties-common ca-certificates curl gnupg unzip git acl cron \
    nginx redis-server supervisor certbot python3-certbot-nginx unattended-upgrades postgresql-common

log "PHP ${PHP_VERSION}…"
if ! command -v "php${PHP_VERSION}" >/dev/null 2>&1; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi
apt-get install -y "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-pgsql" \
    "php${PHP_VERSION}-intl" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-redis" "php${PHP_VERSION}-opcache"

# The stock pool stays (php-fpm needs at least one) but uses no memory until hit.
sed -i -e 's/^pm = .*/pm = ondemand/' -e 's/^;\?pm.process_idle_timeout = .*/pm.process_idle_timeout = 10s/' \
    "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"

log "PostgreSQL ${PG_VERSION}…"
if [ ! -d "/usr/lib/postgresql/${PG_VERSION}" ]; then
    /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y
    apt-get install -y "postgresql-${PG_VERSION}"
fi

log "Redis: local only, bounded memory…"
sed -i -e 's/^# *maxmemory .*/maxmemory 256mb/' -e 's/^# *maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf

log "nginx…"
rm -f /etc/nginx/sites-enabled/default
cat > /etc/nginx/conf.d/mvp.conf <<'NGINX'
# Shared by every MVP site. Films go up to 200 MB.
client_max_body_size 300m;
server_tokens off;
# gzip itself is switched on in Ubuntu's nginx.conf; these widen what it covers.
gzip_vary on;
gzip_min_length 1024;
gzip_proxied any;
gzip_types text/plain text/css application/json application/javascript application/xml+rss image/svg+xml;
NGINX

log "Folders…"
install -d -o "$DEPLOY_USER" -g www-data -m 755 /var/www
install -d -o postgres -g postgres -m 750 /var/backups/mvp

# The deploy user may restart MVP workers and reload PHP without a password
# (Lightsail's ubuntu user already has full sudo; this covers other users).
if [ "$DEPLOY_USER" != "root" ]; then
    cat > /etc/sudoers.d/mvp-deploy <<SUDO
${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/supervisorctl, /usr/bin/systemctl reload php${PHP_VERSION}-fpm, /usr/bin/systemctl reload nginx
SUDO
    chmod 440 /etc/sudoers.d/mvp-deploy
fi

log "Nightly database backups (kept 14 days)…"
cat > /etc/cron.d/mvp-backups <<'CRON'
# Every MVP database, dumped nightly to /var/backups/mvp.
30 2 * * * postgres for db in $(psql -Atc "select datname from pg_database where not datistemplate and datname <> 'postgres'"); do pg_dump -Fc "$db" > /var/backups/mvp/"$db"-$(date +\%Y\%m\%d).dump; done; find /var/backups/mvp -name '*.dump' -mtime +14 -delete
CRON

if ! swapon --show 2>/dev/null | grep -q . && [ -d /run/systemd/system ]; then
    mem_mb=$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)
    if [ "$mem_mb" -lt 4000 ]; then
        log "Adding a 2 GB swap file (RAM: ${mem_mb} MB)…"
        fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
        echo '/swapfile none swap sw 0 0' >> /etc/fstab
        echo 'vm.swappiness=10' > /etc/sysctl.d/99-swappiness.conf && sysctl -p /etc/sysctl.d/99-swappiness.conf
    fi
fi

dpkg-reconfigure -f noninteractive unattended-upgrades || true

nginx -t
"php-fpm${PHP_VERSION}" -t

for s in postgresql redis-server "php${PHP_VERSION}-fpm" nginx supervisor cron; do
    svc enable "$s" >/dev/null 2>&1 || true
    svc restart "$s"
done

log "Done. Next, for each MVP:  sudo bash add-app.sh <app> <domain>"
