# HolidayHub — multi-stage image
# ---------------------------------------------------------------------------
# Targets:
#   dev   → full source + dev dependencies, runs `artisan serve` (hot reload)
#   prod  → optimised, no dev dependencies, php-fpm behind nginx
#
# Build (prod):
#   docker build --target prod -t holidayhub/app:latest .
#
# NOTE: `livewire/flux` resolves from public GitHub, so no Composer credentials
# are required. If that ever changes, mount an auth.json as a build secret:
#   docker build --secret id=flux_auth,src=./auth.json --target prod -t ... .
# ---------------------------------------------------------------------------

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22-bookworm


# ===========================================================================
# Stage 1 — Frontend assets
# ===========================================================================
FROM node:${NODE_VERSION}-slim AS assets

WORKDIR /build

# laravel-vite-plugin reads .env at build time. Provide deterministic defaults
# so the image builds without a host .env present.
ARG APP_NAME=HolidayHub
RUN printf 'APP_NAME=%s\nVITE_APP_NAME=%s\n' "$APP_NAME" "$APP_NAME" > .env

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

COPY vite.config.js tailwind.config.js postcss.config.js jsconfig.json ./
COPY resources/ ./resources/

# Fail the build if assets are broken — better than a blank page in production.
RUN npm run build


# ===========================================================================
# Stage 2 — Composer dependencies
# ===========================================================================
FROM composer:2.8 AS vendor-base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

WORKDIR /app
COPY composer.json composer.lock ./

# Optional Composer credentials (not required for the current dependency set).
RUN --mount=type=secret,id=flux_auth,required=false \
    if [ -f /run/secrets/flux_auth ]; then \
        mkdir -p /root/.composer && cp /run/secrets/flux_auth /root/.composer/auth.json; \
    fi

# --no-scripts: package discovery needs the full source tree, so it runs later.
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --prefer-dist --no-scripts --no-autoloader

# Split the two dependency sets so the prod image carries no dev packages.
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --prefer-dist --no-scripts --no-autoloader --optimize-autoloader


# ===========================================================================
# Stage 3 — Base PHP runtime (shared by dev and prod)
# ===========================================================================
# Debian (not Alpine) is deliberate: Alpine/musl does not provide pcntl, posix,
# sysvmsg or shmop, which Laravel queue workers need for graceful timeouts and
# signal handling.
FROM php:${PHP_VERSION}-fpm-bookworm AS base

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        ca-certificates \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libwebp-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        posix \
        sockets \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY docker/php/php.ini  /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

WORKDIR /var/www/html

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php-fpm"]


# ===========================================================================
# Stage 4 — Development image
# ===========================================================================
FROM base AS dev

# Composer + Node CLIs so the bind-mounted dev container can self-bootstrap.
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY --from=vendor-base /app/vendor/            ./vendor/
COPY . .

RUN composer dump-autoload --optimize \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]


# ===========================================================================
# Stage 5 — Production image
# ===========================================================================
FROM base AS prod

COPY --from=vendor-base /app/vendor/ ./vendor/
COPY . .

# package discovery + optimized autoloader (replaces the --no-scripts install)
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && composer run-script post-autoload-dump --no-interaction --no-dev \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm -rf /root/.composer

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
