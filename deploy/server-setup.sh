#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# One-time setup of a fresh Lightsail Ubuntu instance for HolidayHub.
#
#   curl -fsSL https://raw.githubusercontent.com/uncleBandit/HolidayHub/main/deploy/server-setup.sh | sudo bash
#   (or copy this file to the server and run: sudo bash server-setup.sh)
#
# Installs Docker Engine + the compose plugin, lets the ubuntu user run docker
# (the deploy logs in as ubuntu), creates /opt/holidayhub, adds swap on small
# instances, and turns on automatic security updates. Safe to run again.
# ---------------------------------------------------------------------------
set -euo pipefail

DEPLOY_USER="${DEPLOY_USER:-ubuntu}"
APP_DIR=/opt/holidayhub

log() { printf '\033[0;36m[setup]\033[0m %s\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then
    echo "Run as root: sudo bash $0" >&2
    exit 1
fi

log "Installing Docker…"
if ! command -v docker >/dev/null 2>&1; then
    apt-get update -y
    apt-get install -y ca-certificates curl
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc
    . /etc/os-release
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update -y
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi
systemctl enable --now docker
usermod -aG docker "$DEPLOY_USER"

# Keep container logs from filling the disk.
if [ ! -f /etc/docker/daemon.json ]; then
    cat > /etc/docker/daemon.json <<'JSON'
{ "log-driver": "json-file", "log-opts": { "max-size": "20m", "max-file": "5" } }
JSON
    systemctl restart docker
fi

log "Creating ${APP_DIR}…"
install -d -o "$DEPLOY_USER" -g "$DEPLOY_USER" -m 750 "$APP_DIR"

# Small Lightsail plans (1–2 GB RAM) need swap for PHP, Postgres and Redis together.
if ! swapon --show | grep -q .; then
    mem_mb=$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)
    if [ "$mem_mb" -lt 4000 ]; then
        log "Adding a 2 GB swap file (RAM: ${mem_mb} MB)…"
        fallocate -l 2G /swapfile
        chmod 600 /swapfile
        mkswap /swapfile
        swapon /swapfile
        echo '/swapfile none swap sw 0 0' >> /etc/fstab
        sysctl -w vm.swappiness=10
        echo 'vm.swappiness=10' > /etc/sysctl.d/99-swappiness.conf
    fi
fi

log "Enabling automatic security updates…"
apt-get install -y unattended-upgrades
dpkg-reconfigure -f noninteractive unattended-upgrades

log "Done. Log out and back in (so the docker group applies), then:"
echo "  1. put the production .env in ${APP_DIR}/.env  (template: deploy/.env.production.example)"
echo "  2. open ports 80 and 443 in the Lightsail firewall (Networking tab)"
echo "  3. run the 'deploy' workflow from GitHub Actions"
