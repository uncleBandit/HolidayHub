# MVP showcase server

One small server (Lightsail or DigitalOcean, Ubuntu 24.04) hosts every MVP.
Each MVP repository deploys itself to it with its own pipeline.

```
GitHub: MVP 1   MVP 2   MVP 3   MVP 4   MVP 5      each repo: tests → deploy-mvp
           \      |       |       |      /
            ──────── SSH deploy ─────────
                         │
                  Ubuntu server
                    nginx  (a site + HTTPS certificate per MVP)
        ┌──────────┬────┴─────┬──────────┐
     PHP-FPM    PHP-FPM    PHP-FPM    PHP-FPM     a pool per MVP, started on demand
        └──────────┴────┬─────┴──────────┘
          PostgreSQL (a database + user per MVP)
          Redis (a key prefix per MVP) · supervisor (a queue worker per MVP)
```

**Each MVP has:**
- **Its own pipeline:** tests, then build, then deploy.
- **Its own database and credentials.** Other MVPs can't connect to it.
- **Its own folder:** `/var/www/<app>`, holding `releases/`, a `current` link, and `shared/.env` plus `shared/storage`.
- **Its own worker and scheduler.**
- **Its own logs:**
  - app and worker: `/var/www/<app>/shared/storage/logs/`
  - nginx: `/var/log/nginx/<app>.*.log`

When an MVP graduates, it moves to its own server with the Docker setup in
[`../README.md`](../README.md).

## How a deploy works (`.github/workflows/deploy-mvp.yml`)
1. The **tests** workflow passes on `main`.
2. **deploy-mvp** builds the release on GitHub, with `composer install --no-dev` and `npm run build`, so the server never builds anything.
3. The release is uploaded to `/var/www/<app>/releases/<id>`, and [`release.sh`](release.sh) runs on the server:
   - links `shared/.env` and `shared/storage`
   - runs `php artisan migrate`
   - runs `php artisan demo:seed`, which **seeds the demo data only when the database is empty**, so redeploys never duplicate it or wipe what was created during a showcase
   - caches config, routes and views
   - **switches `current` to the new release in one step**, and restarts the queue worker
   - checks `/up`; if the new release doesn't answer, it **switches back** to the previous release and the run fails
   - keeps the last 5 releases
4. The workflow checks the public URL.

**Reset a demo:** Actions → **deploy-mvp** → Run workflow → tick **Reset the demo data**.
This drops the MVP's database and reseeds a clean demo, including the admin from `shared/.env`.

## One-time server setup
1. **Create the server.**
   - Ubuntu 24.04, 2 GB RAM recommended; 1 GB works for idle demos thanks to swap.
   - Attach a **static IP** (Lightsail: Networking → Static IP; DigitalOcean: Reserved IP).
   - Open ports **80 and 443** in the provider's firewall.
2. **Run the setup script** as the `ubuntu` user (Lightsail) or your sudo user:
   ```bash
   curl -fsSLO https://raw.githubusercontent.com/uncleBandit/HolidayHub/main/deploy/mvp/server-setup.sh
   curl -fsSLO https://raw.githubusercontent.com/uncleBandit/HolidayHub/main/deploy/mvp/add-app.sh
   sudo DEPLOY_USER=ubuntu bash server-setup.sh
   ```
3. **Create a deploy key** on your own machine:
   ```bash
   ssh-keygen -t ed25519 -f mvp_deploy -N "" -C "github-actions-mvp"
   ssh-copy-id -i mvp_deploy.pub ubuntu@<static-ip>
   ```
   Keep the private key `mvp_deploy`: every MVP repository uses it as `DEPLOY_SSH_KEY`.

## Adding an MVP (HolidayHub as the example)
1. **DNS:** an **A record** `holidayhub` → the static IP. Check it with `dig +short holidayhub.unclecanvas.com`.
2. **On the server:**
   ```bash
   sudo DEPLOY_USER=ubuntu bash add-app.sh holidayhub holidayhub.unclecanvas.com
   nano /var/www/holidayhub/shared/.env
   ```
   In `.env`, set `ADMIN_EMAIL` and `ADMIN_PASSWORD` (16+ characters) and any API keys (Google, Maps).
   The database password, app key, Redis prefix and session cookie are already filled in.
3. **In the GitHub repo:** Settings → Secrets and variables → Actions:

   | Kind | Name | Value |
   |---|---|---|
   | secret | `DEPLOY_SSH_KEY` | contents of `mvp_deploy` |
   | variable | `DEPLOY_HOST` | the static IP |
   | variable | `DEPLOY_USER` | `ubuntu` |
   | variable | `MVP_APP` | `holidayhub` (the name given to add-app.sh) |
   | variable | `APP_URL` | `https://holidayhub.unclecanvas.com` |

4. **First deploy:** Actions → **deploy-mvp** → Run workflow. After that, every green push to `main` deploys.

For HolidayHub only, add these to the OAuth client in Google Cloud Console:
- redirect URI `https://holidayhub.unclecanvas.com/auth/google/callback`
- JavaScript origin `https://holidayhub.unclecanvas.com`

## Bringing another MVP repository onto this pipeline
Copy into that repository:
- `.github/workflows/deploy-mvp.yml` (unchanged)
- `deploy/mvp/release.sh` (unchanged)
- a `tests` workflow, since deploy-mvp runs after it (`.github/workflows/tests.yml` here is a template; it needs Postgres for a Postgres app)

Give the app a `php artisan demo:seed` command (HolidayHub's is in `routes/console.php`). It should:
- seed the demo data when the database is empty
- support `--fresh` to rebuild and reseed

Without it, deploys still migrate but skip the demo data. Then follow **Adding an MVP**.
The scripts assume Laravel on PHP 8.4. Another stack needs its own `release.sh` and nginx site.

## Day to day (on the server)

| Task | Command |
|---|---|
| Worker status | `sudo supervisorctl status` |
| App log | `tail -f /var/www/<app>/shared/storage/logs/laravel-*.log` |
| Artisan | `cd /var/www/<app>/current && php artisan <command>` |
| Roll back by hand | `ln -sfn /var/www/<app>/releases/<older-id> /var/www/<app>/current && sudo supervisorctl restart <app>-worker` |
| Backups | nightly dumps of every MVP database in `/var/backups/mvp` (14 days) |
| Memory | `free -h`. If a demo is slow, move to a bigger plan (snapshot, then a new instance; keep the static IP) |
