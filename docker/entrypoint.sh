#!/bin/sh
set -e

echo "========================================="
echo "  Recruit - Container Entrypoint"
echo "========================================="

# Wait for PostgreSQL (extra safety beyond healthcheck)
echo "[entrypoint] Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "[entrypoint] PostgreSQL is ready."

# Run setup on first boot (check if migrations table exists)
if ! php artisan migrate:status > /dev/null 2>&1; then
    echo "[entrypoint] First boot detected — running initial setup..."
    /bin/sh /var/www/html/docker/setup.sh
else
    echo "[entrypoint] Database already initialized — running pending migrations..."
    php artisan migrate --force
fi

# Always ensure storage link and caches
php artisan storage:link 2>/dev/null || true
php artisan icons:cache 2>/dev/null || true
php artisan filament:upgrade --no-interaction 2>/dev/null || true

echo "[entrypoint] Setup complete. Starting $@"
exec "$@"
