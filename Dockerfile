# syntax=docker/dockerfile:1
# Production image for the demo deploy (Render free + Neon).
# Local development still uses Sail (compose.yaml) — do not use this for dev.
# See docs/todos/demo_deployment/demo_deploy.md

# ---------- Stage 1: Vite assets ----------
FROM node:24-bookworm-slim AS assets
WORKDIR /app
# Do not copy .npmrc (ignore-scripts=true) — Vite/Rolldown needs native bindings.
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage 2: PHP runtime ----------
FROM php:8.4-apache-bookworm AS runtime

RUN set -eux; \
    for attempt in 1 2 3; do \
        apt-get update \
        && apt-get install -y --no-install-recommends --fix-missing \
            libpq-dev \
            libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
            libzip-dev libicu-dev libonig-dev \
            unzip git \
        && break; \
        echo "apt-get failed (attempt ${attempt}), retrying..."; \
        rm -rf /var/lib/apt/lists/*; \
        sleep 3; \
    done \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql gd intl bcmath zip exif pcntl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Keep the demo (512MB free tier) from spawning Apache workers unboundedly.
RUN printf 'StartServers 2\nMinSpareServers 2\nMaxSpareServers 4\nMaxRequestWorkers 25\n' \
        >> /etc/apache2/mods-available/mpm_prefork.conf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && cp .env.example .env \
    && php artisan package:discover --ansi \
    && rm .env \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
