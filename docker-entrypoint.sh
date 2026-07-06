#!/bin/sh
set -e

cd /var/www/html

# Ensure writable directories exist (important when storage/ is a mounted volume)
mkdir -p storage/app/public \
         storage/logs \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache

# Clear any build-time cached config — runtime env vars must be used
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache using runtime environment
php artisan config:cache
php artisan route:cache || echo "WARNING: route:cache failed — duplicate route names detected."
php artisan view:cache

# Create storage symlink (--force is safe to re-run on every deploy)
php artisan storage:link --force

# Wait for MySQL to accept TCP connections (fast check — no Laravel bootstrap overhead)
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
MAX_TRIES=30
TRIES=0
until (echo > /dev/tcp/${DB_HOST}/${DB_PORT}) 2>/dev/null; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge "$MAX_TRIES" ]; then
        echo "ERROR: DB not reachable after ${MAX_TRIES} attempts. Skipping migrations."
        break
    fi
    echo "  DB not ready (attempt ${TRIES}/${MAX_TRIES}) — retrying in 2s..."
    sleep 2
done

# Run migrations if DB port is open
if (echo > /dev/tcp/${DB_HOST}/${DB_PORT}) 2>/dev/null; then
    php artisan migrate --force && echo "Migrations OK." || echo "WARNING: migrate failed — check logs."
fi

# Generate Passport keys only if they do not already exist.
# NEVER run passport:key unconditionally — it rotates keys and logs out all users.
if [ -z "$PASSPORT_PRIVATE_KEY" ] && [ ! -f "storage/oauth-private.key" ]; then
    php artisan passport:keys || echo "WARNING: passport:keys failed."
fi

# Fix ownership so php-fpm (www-data) can write to these directories
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
