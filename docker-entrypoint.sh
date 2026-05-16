#!/bin/sh
set -e

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
php artisan route:cache
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
