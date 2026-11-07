# Deploying HolidayHub to its own server (production, later)

> **MVP showcase:** HolidayHub currently runs on the shared MVP server. See
> [`mvp/README.md`](mvp/README.md). This page is for when it graduates to a
> dedicated server; its workflow (`deploy-production`) only runs by hand.

Production: **https://holidayhub.unclecanvas.com**, one Lightsail Ubuntu instance
running the stack in [`compose.prod.yaml`](../compose.prod.yaml):

| Service | What it does |
|---|---|
| `web` | Caddy: HTTPS (Let's Encrypt, automatic), serves `public/` and uploads, passes PHP to `app` |
| `app` | Laravel on php-fpm |
| `queue`, `scheduler` | Background jobs and scheduled tasks |
| `postgres`, `redis` | Database; cache, sessions and queue |
| `backup` | Nightly `pg_dump` into the `postgres-backups` volume (kept 14 days) |

How a release happens: run **deploy-production** by hand; it builds the
`app` and `web` images, pushes them to GHCR (`ghcr.io/unclebandit/holidayhub`),
then over SSH the server pulls them, runs migrations and the production seeder,
and restarts. The job fails if `https://holidayhub.unclecanvas.com/up` does not
answer afterwards. Until the server is set up the deploy step is skipped.

## One-time setup

### 1. Lightsail instance
- **RAM:** use at least the 2 GB plan; 1 GB works only with the swap file the setup script adds.
- **Static IP:** Networking → Create static IP, and attach it to the instance. Without one, the IP changes on every stop/start.
- **Firewall:** in Networking → IPv4 Firewall, allow **HTTP 80** and **HTTPS 443**. SSH 22 is already open.

### 2. DNS
At the DNS host for `unclecanvas.com`, add an **A record**: `holidayhub` → the static IP.
Check it before going on, since HTTPS needs it:
```bash
dig +short holidayhub.unclecanvas.com    # must print the static IP
```

### 3. Prepare the server
```bash
ssh ubuntu@<static-ip>
curl -fsSL https://raw.githubusercontent.com/uncleBandit/HolidayHub/main/deploy/server-setup.sh | sudo bash
exit   # log out and in again so the docker group applies
```
This installs Docker, creates `/opt/holidayhub`, adds swap on small plans and turns on security updates.

### 4. Production settings
```bash
ssh ubuntu@<static-ip>
nano /opt/holidayhub/.env     # paste deploy/.env.production.example, fill every CHANGE_ME
chmod 600 /opt/holidayhub/.env
```
Generate secrets on the server:
```bash
echo "base64:$(openssl rand -base64 32)"   # APP_KEY
openssl rand -base64 32 | tr -d '/+='      # POSTGRES_PASSWORD, REDIS_PASSWORD
```
Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` (at least 16 characters) to create the first
admin on the first deploy. You can clear them afterwards.

### 5. A deploy key for GitHub Actions
On your own machine:
```bash
ssh-keygen -t ed25519 -f holidayhub_deploy -N "" -C "github-actions-deploy"
ssh-copy-id -i holidayhub_deploy.pub ubuntu@<static-ip>   # or append the .pub to ~/.ssh/authorized_keys
```
In GitHub → **Settings → Secrets and variables → Actions**:
- **Secrets:** `LIGHTSAIL_SSH_KEY` = the contents of the private key `holidayhub_deploy`.
- **Variables:**
  - `LIGHTSAIL_HOST` = the static IP
  - `LIGHTSAIL_USER` = `ubuntu`
  - `APP_URL` = `https://holidayhub.unclecanvas.com`

Then delete the local private key file.

### 6. Google sign-in
In Google Cloud Console → Credentials → your OAuth client, add:
- Authorised JavaScript origin: `https://holidayhub.unclecanvas.com`
- Authorised redirect URI: `https://holidayhub.unclecanvas.com/auth/google/callback`

Also allow the domain on the Maps API key's HTTP-referrer restrictions.

### 7. First deploy
GitHub → **Actions → deploy → Run workflow**. Every green push to `main` deploys after that.

## Day to day
On the server: `cd /opt/holidayhub`, and use `dc() { docker compose -f compose.prod.yaml "$@"; }` as a shorthand.

| Task | Command |
|---|---|
| Status | `dc ps` |
| Logs | `dc logs -f app web queue` |
| Artisan | `dc exec app php artisan <command>` |
| Roll back to a release | set `IMAGE_TAG=<older sha>` in `.env`, then `dc up -d` (tags are listed under the repo's Packages) |
| List backups | `dc exec backup ls -lh /backups` |
| Copy a backup off the server | `docker cp holidayhub-prod-backup-1:/backups/<file>.dump .` then `scp` it home |
| Restore a backup | `dc exec -T postgres pg_restore -U holidayhub -d holidayhub --clean --if-exists < file.dump` |

Also enable **Lightsail automatic snapshots** (instance → Snapshots) for whole-machine backups.

## Still to do
- **Real email:** set up Amazon SES, set `MAIL_MAILER=smtp` and the SES SMTP settings in `.env`. Until then mail goes to the logs.
- **Payments:** `BOOKING_TEST_MODE=true` confirms bookings without charging.
