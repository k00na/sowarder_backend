#!/usr/bin/env bash
# Demo-deploy entrypoint (Render). Runs migrations (direct/unpooled Neon
# connection), optionally seeds, links storage, caches config, then starts
# Apache on $PORT (Render injects PORT; default 80 for a local docker run).
set -e

cd /var/www/html

# --- Apache port (Render assigns $PORT, commonly 10000) ---
export PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# --- Migrations: direct (unpooled) connection, per Neon pooling guidance ---
# PgBouncer transaction pooling breaks session-level migration operations,
# so point DB_URL at DATABASE_URL_UNPOOLED for artisan migrate/seed only.
# App traffic keeps using the pooled DATABASE_URL (config/database.php
# falls back to it when DB_URL is unset).
run_artisan_direct() {
    if [ -n "${DATABASE_URL_UNPOOLED:-}" ]; then
        DB_URL="${DATABASE_URL_UNPOOLED}" php artisan "$@"
    else
        php artisan "$@"
    fi
}

run_artisan_direct migrate --force

# --- Optional demo seed (flag-gated) ---
# RUN_DEMO_SEED=true seeds the two demo restaurants. WARNING: the seeder
# resets demo users to password "password" on every run (updateOrCreate),
# so leave this unset/false after Phase 4 password hardening.
if [ "${RUN_DEMO_SEED:-false}" = "true" ]; then
    echo "RUN_DEMO_SEED=true — seeding demo data (demo passwords reset to 'password')"
    run_artisan_direct db:seed --force
fi

php artisan storage:link || true
php artisan config:cache

exec "$@"
