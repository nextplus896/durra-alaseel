#!/bin/bash
set -e

echo "🚀 Starting Deployment..."

# 1. تفعيل وضع الصيانة (Maintenance Mode)
# يمكنك الدخول للموقع أثناء الصيانة عبر الرابط: yoursite.com/secret-token-123
php artisan down --secret="secret-token-123" --render="errors::503" || true

# 2. سحب آخر التعديلات من Git
echo "📥 Pulling latest changes from Git..."
git fetch origin main
git reset --hard origin/main

# 3. تحديث حزم Composer للإنتاج (بدون حزم dev)
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 4. بناء ملفات الواجهة (Vite / NPM) إن وجدت تعديلات
if [ -f "package.json" ]; then
    echo "🎨 Building frontend assets..."
    npm ci --no-audit || npm install --no-audit
    npm run build
fi

# 5. تشغيل الـ Migrations لقاعدة البيانات
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 6. مسح وإعادة بناء الكاش للأداء العالي
echo "⚡ Optimizing cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. التأكد من رابط التخزين storage symlink
php artisan storage:link || true

# 8. ضبط الصلاحيات للمجلدات الحساسة
echo "🔒 Fixing directory permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 9. إعادة تشغيل الـ Queue Workers (إن وجدت)
php artisan queue:restart || true

# 10. عمل Reload لـ PHP-FPM لتفريغ OPcache
echo "🔄 Reloading PHP-FPM..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
sudo systemctl reload "php${PHP_VERSION}-fpm" || sudo systemctl reload php-fpm || true

# 11. إيقاف وضع الصيانة وإعادة الموقع للعمل
php artisan up

echo "✅ Deployment completed successfully!"
