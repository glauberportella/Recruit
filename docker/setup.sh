#!/bin/sh
set -e

echo "========================================="
echo "  Recruit - Development Setup"
echo "========================================="

cd /var/www/html

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[setup] Generating application key..."
    php artisan key:generate --force
fi

# Run all migrations (includes pgvector extension creation)
echo "[setup] Running migrations..."
php artisan migrate --force

# Seed database
echo "[setup] Seeding database..."
php artisan db:seed --force

# Sync permissions
echo "[setup] Syncing roles & permissions..."
php artisan permissions:sync -C -Y

# Storage link
echo "[setup] Creating storage link..."
php artisan storage:link 2>/dev/null || true

# Cache icons
echo "[setup] Caching icons..."
php artisan icons:cache 2>/dev/null || true

echo "========================================="
echo "  Setup complete!"
echo ""
echo "  App:     http://localhost:${APP_PORT:-8800}"
echo "  Mailpit: http://localhost:${MAILPIT_DASHBOARD_PORT:-8026}"
echo "  DB Port: ${DB_PORT:-5433} (host)"
echo ""
echo "  Login:   superuser@mail.com / password"
echo "========================================="
