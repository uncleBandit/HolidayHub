# HolidayHub

HolidayHub is a modular travel and hospitality marketplace built with Laravel.
Guests can discover destinations, stays, activities, and travel packages;
providers and travel agents manage their offerings; and platform staff
administer accounts and provider verification. The Media module also provides
moderated provider videos and a public reel stream.

## What the project includes

- **Discovery and catalog:** destinations, hotels, villas, bed and breakfasts,
  activities, experiences, amenities, and promotional offers.
- **Booking:** reservations, booking status, pricing, availability calendars,
  and protections against conflicting bookings.
- **Marketplace accounts:** guests, service providers, travel agents, roles,
  permissions, profiles, and API tokens.
- **Packages and saved items:** agent-curated travel packages and guest
  wishlists.
- **Reviews:** guest ratings and provider feedback.
- **Payments:** a payment gateway abstraction with a test-mode gateway for
  local development.
- **Provider media:** private video uploads, moderation, provider media pages,
  and a public, cursor-paginated reel stream.
- **Administration:** a Filament admin panel for platform operations and tenant
  verification workflows.

## Technology

- PHP 8.4 in Docker (Composer requirement: PHP `^8.2`)
- Laravel 12, Livewire 3, Blade, and Filament 4
- Vue 3 and Inertia are available for frontend pages that use them
- PostgreSQL 17 for application data
- Redis for queues, cache, sessions, and atomic availability locks
- Node.js 22, npm, Vite 7, and Tailwind CSS 3
- Docker Compose for the recommended development and production environments

## Start a development environment

Docker Compose is the recommended way to run the full stack. It starts the
application, PostgreSQL, Redis, and Mailpit. Node builds the Vite assets as
part of the image build.

Requirements: Docker Engine and the Docker Compose plugin.

```bash
cp .env.docker.example .env
```

Generate an application key using Docker's PHP image and put the output in
`APP_KEY` in `.env`:

```bash
docker run --rm php:8.4-cli php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

Build and start the development stack:

```bash
docker compose up --build -d
```

Run migrations and optional demo-data seeders:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Visit:

- Application: <http://localhost:8000>
- Mailpit inbox: <http://localhost:8025>
- Vite development server (optional): <http://localhost:5173>

To use Vite hot module replacement during frontend development, start its
profile explicitly:

```bash
docker compose --profile dev-assets up -d vite
```

Stop the development services without removing database or Redis data:

```bash
docker compose down
```

> **Data warning:** `docker compose down -v` removes named volumes, including
> the PostgreSQL and Redis data. Use it only when you intentionally want to
> delete that data.

## Local development without Docker

For a host-based setup, install PHP 8.2 or newer with the extensions required
by Laravel and the locked Composer packages (including `intl`, PostgreSQL,
`zip`, and Redis support), Composer 2, Node.js 22, npm, PostgreSQL, and Redis.

```bash
cp .env.example .env
composer install
npm ci
php artisan key:generate
```

Configure `.env` for a reachable PostgreSQL database and Redis server, then:

```bash
php artisan migrate
php artisan db:seed
npm run build
```

For a frontend watch server, run `npm run dev` in one terminal. Start the
Laravel server in another:

```bash
php artisan serve
```

Do not commit `.env`, credentials, API keys, or uploaded media.

## Common commands

Run these from the project root. Prefix application commands with
`docker compose exec app` when using the Docker setup.

```bash
php artisan route:list             # List registered web and API routes
php artisan migrate                # Apply pending migrations
php artisan db:seed                # Seed development/demo records
php artisan test                   # Run Pest/PHPUnit tests
vendor/bin/pint                    # Format PHP
npm run dev                        # Start Vite with hot module replacement
npm run build                      # Build frontend assets
```

The database seeder coordinates module seeders in dependency order. For a
single module, use the module seeding command:

```bash
php artisan module:seed <module-id>
```

### Running tests in Docker

The test suite is configured for PostgreSQL database `holidayhub_test`.
Create that database once if it does not exist:

```bash
docker compose exec postgres createdb -U holidayhub holidayhub_test
```

Run tests with explicit test database settings:

```bash
docker compose exec \
  -e DB_HOST=postgres \
  -e DB_DATABASE=holidayhub_test \
  app php artisan test
```

The explicit overrides matter because Compose injects the development
database environment into the container. **Never point test commands at the
development or production database.** Feature tests use Laravel's
`RefreshDatabase` trait and may reset the selected test database.

## Main routes

| URL | Purpose | Access |
| --- | --- | --- |
| `/` | Public landing page | Public |
| `/destinations` | Browse destinations | Public |
| `/hotels` | Browse hotels | Public |
| `/bed-and-breakfasts` | Browse B&Bs | Public |
| `/reels` | Browse approved provider reels | Public |
| `/providers/{provider}/media` | View a provider's published media | Public |
| `/login`, `/register` | Account access | Public |
| `/dashboard`, `/discover` | Guest discovery and account area | Signed in and verified |
| `/provider/dashboard` | Provider dashboard | Verified provider |
| `/provider/media` | Upload and manage provider videos | Verified provider |
| `/agent/dashboard` | Agent dashboard | Verified agent |
| `/admin` | Filament administration control plane | Admin permission |
| `/admin/media-moderations` | Review provider media submissions | Media moderation permission |

The versioned API lives under `/api/v1`. It includes authentication and
protected resources for profiles, dashboards, bookings, hotels, offers,
reviews, rooms, activities, destinations, and packages. Most API resource
routes require a Sanctum-authenticated user; provider and agent dashboard
routes also enforce their corresponding role.

Administration stays in Filament and uses action-specific Spatie permissions.
Run the `administration` module seeder to create the operational role presets
and permissions. The bootstrap super-admin account is created only when
`ADMIN_EMAIL` and `ADMIN_PASSWORD` are explicitly configured; the password
must contain at least 16 characters. No default administrator credentials are
seeded.

## Project structure

The application is a modular monolith. Domain code is grouped in
`app/Modules/<Module>` rather than split into separate services:

```text
app/
  Modules/
    <Module>/
      Application/       # Use cases and application services
      Config/            # Module configuration
      Database/          # Migrations, factories, and seeders
      Domain/            # Models, enums, contracts, and policies
      Presentation/      # HTTP controllers, Livewire components, and routes
      Resources/views/   # Module Blade views
      module.json        # Module identity and metadata
  Providers/             # Application and module bootstrap
  Support/               # Shared module registry and infrastructure
database/
  migrations/            # Cross-module/platform migrations
  seeders/               # Global seed orchestration
resources/
  css/
  js/
  views/                 # Shared Blade views and layouts
routes/                  # Application web, API, auth, and console routes
tests/
```

Enabled modules are discovered from `app/Modules/*/module.json`. Module-owned
migrations, factories, views, routes, Livewire components, and service
providers are registered by the module bootstrapper. Cross-module migrations
belong in `database/migrations`; use a migration timestamp later than the
tables it references.

| Module | Responsibility |
| --- | --- |
| Accommodation | Hotels, villas, B&Bs, room types, rooms, and rates |
| Activities | Tours, excursions, and experiences |
| Administration | Admin operations, onboarding, and tenant verification |
| Agents | Travel-agent accounts and workflows |
| Audit | Audit trail |
| Auth | Authentication and account lifecycle |
| Availability | Inventory calendars, capacity, and blackout windows |
| Booking | Reservations and booking lifecycle |
| Catalog | Offers, discounts, and amenities |
| Destinations | Destination taxonomy and discovery |
| Identity | Users, guests, roles, permissions, and API tokens |
| Media | Image galleries and provider videos/reels |
| Packages | Agent-assembled travel packages |
| Payments | Payment gateway abstraction |
| Pricing | Base and seasonal pricing |
| Providers | Provider profiles and services |
| Reviews | Ratings, feedback, and moderation |
| Wishlist | Saved destinations and accommodation |

## Sign-in and provider registration

Guests can create or access their account with **Continue with Google** from
`/login` or `/register`; guest self-registration no longer asks them to set a
password. Service providers can register with email and password or Google at
`/register/provider`. Provider applications start in the pending verification
queue. Administrator accounts must continue using email and password and
cannot authenticate through Google.

Create a Google OAuth 2.0 web client and register the exact callback URL for the
deployment (for example, `https://holidayhub.example/auth/google/callback`).
Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in
`.env` to those values, then clear cached configuration after changing them.
The local and Docker environment templates include these variables. Google
accounts must return a verified email address; that verified address is used
to link an existing non-admin account, and the Google ID is stored to support
future sign-ins.

## Provider media and reels

Provider media is managed separately from the existing `images` gallery:

1. Providers upload a video from `/provider/media`.
2. Uploads are private and remain `pending_review` until an admin reviews them.
3. An admin can approve a submission for publication or reject it with a
   review note.
4. Published reels appear in `/reels`; published reels and videos appear on
   the provider's media page. Providers can optionally attach a post to one of
   their activities so it also appears on that activity's detail page.

Current upload limits are MP4 or WebM video up to 10 MB and an optional JPEG,
PNG, or WebP cover image up to 3 MB. Media uses the private `local` disk by
default; set `MEDIA_DISK` to a configured private storage disk for deployments.
The current implementation plays uploaded files directly. Transcoding, HLS,
large direct-to-object-storage uploads, engagement tracking, and personalized
recommendations are not implemented yet.

## Docker images and production

The multi-stage [Dockerfile](./Dockerfile) builds frontend assets with Node,
installs Composer dependencies against the PHP runtime extensions, and
provides separate `dev` and `prod` targets.

The production Compose stack is in [compose.prod.yaml](./compose.prod.yaml).
It includes PHP-FPM, nginx, PostgreSQL, Redis, queue workers, and a scheduler.
Configure the required production values in `.env` first; the compose file
requires `APP_KEY`, `APP_URL`, and PostgreSQL credentials and expects real
Redis and SMTP settings.

```bash
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml up -d
docker compose -f compose.prod.yaml run --rm app migrate
```

Migrations are an explicit release step and should be run once, not
automatically by every application replica. Configure TLS certificates and
production storage/secrets for your deployment environment before exposing
the stack publicly. Back up PostgreSQL and uploaded media independently.

The development Compose configuration also supports an optional Vite service
and a Mailpit inbox. Its default test/payment and development credentials are
for local use only.

## CI

GitHub Actions runs the test workflow for pushes and pull requests targeting
`main` or `develop`. It installs Composer and npm dependencies, builds the
frontend, and runs the PHP test suite. The workflow expects its configured
Composer credentials for the licensed Flux package.

## More detail

- [Media module](./app/Modules/Media/README.md) — provider-video workflow,
  storage, and current media limitations.
- [Development Compose file](./compose.yaml)
- [Production Compose file](./compose.prod.yaml)
- [Environment template for Docker](./.env.docker.example)
