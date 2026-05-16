#!/bin/sh
set -e

# Force PHP-FPM upstream vars so webdevops/php-nginx 10-init.sh never gets an empty host.
# Coolify or other orchestrators may pass these as empty strings, which crashes nginx.
export PHP_FPM_HOST="${PHP_FPM_HOST:-127.0.0.1}"
export PHP_FPM_PORT="${PHP_FPM_PORT:-9000}"

# Ensure writable directories exist (important when storage/ is a mounted volume)
mkdir -p storage/app/public \
         storage/logs \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache

# Clear any build-time cached config — runtime env vars (from Coolify) must be used
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache using runtime environment
php artisan config:cache
php artisan route:cache || echo "WARNING: route:cache failed — duplicate route names detected. Fix before next deploy."
php artisan view:cache

# Create storage symlink (--force is safe to re-run on every deploy)
php artisan storage:link --force

# Run any pending migrations automatically on each deploy
php artisan migrate --force

# Generate Passport keys only if they do not already exist.
# NEVER run passport:key unconditionally — it rotates keys and logs out all users.
# Preferred: set PASSPORT_PRIVATE_KEY and PASSPORT_PUBLIC_KEY as Coolify env vars instead.
if [ -z "$PASSPORT_PRIVATE_KEY" ] && [ ! -f "storage/oauth-private.key" ]; then
    php artisan passport:keys
fi

exec "$@"
