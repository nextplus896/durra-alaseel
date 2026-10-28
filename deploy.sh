#!/bin/bash
set -e

echo "🚀 Starting Deployment..."

# 0. تجاهل تغيير الصلاحيات (filemode) في git على لينكس
git config core.fileMode false || true

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

# 4. التحقق من وجود Node.js & npm وتثبيتهما تلقائياً إن لم يكونا موجودين
if [ -f "package.json" ]; then
    if ! command -v npm &> /dev/null; then
        echo "⚙️ Node.js & npm not found. Installing automatically..."

        SUDO=""
        if [ "$(id -u)" -ne 0 ] && command -v sudo &> /dev/null; then
            SUDO="sudo"
        fi

        # التأكد من وجود curl
        if ! command -v curl &> /dev/null && command -v apt-get &> /dev/null; then
            $SUDO apt-get update -y && $SUDO apt-get install -y curl || true
        fi

        # تثبيت Node.js v20 LTS مع npm
        if command -v apt-get &> /dev/null; then
            (curl -fsSL https://deb.nodesource.com/setup_20.x | $SUDO -E bash -) && $SUDO apt-get install -y nodejs || true
        elif command -v yum &> /dev/null; then
            (curl -fsSL https://rpm.nodesource.com/setup_20.x | $SUDO bash -) && $SUDO yum install -y nodejs || true
        elif command -v dnf &> /dev/null; then
            (curl -fsSL https://rpm.nodesource.com/setup_20.x | $SUDO bash -) && $SUDO dnf install -y nodejs || true
        fi
    fi

    if command -v npm &> /dev/null; then
        echo "🎨 Building frontend assets with npm ($(node -v) / npm $(npm -v))..."
        npm ci --no-audit || npm install --no-audit
        npm run build
    else
        echo "⚠️ Node.js/npm could not be installed automatically. Skipping frontend build..."
    fi
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
if [ ! -L "public/storage" ]; then
    php artisan storage:link || true
fi

# 8. ضبط الصلاحيات للمجلدات الحساسة بحيث يشترك فيها مستخدم الـ deploy وخادم الويب www-data
echo "🔒 Fixing directory permissions..."
DEPLOY_USER=$(whoami)
sudo usermod -a -G www-data "$DEPLOY_USER" 2>/dev/null || true
sudo chown -R "$DEPLOY_USER:www-data" storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
sudo chmod -R g+s storage bootstrap/cache 2>/dev/null || true

# 9. إعادة تشغيل الـ Queue Workers (إن وجدت)
php artisan queue:restart || true

# 10. عمل Reload لـ PHP-FPM لتفريغ OPcache
echo "🔄 Reloading PHP-FPM..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;" 2>/dev/null || echo "8.2")
sudo systemctl reload "php${PHP_VERSION}-fpm" 2>/dev/null || sudo systemctl reload php-fpm 2>/dev/null || true

# 11. إيقاف وضع الصيانة وإعادة الموقع للعمل
php artisan up || sudo rm -f storage/framework/down || true

echo "✅ Deployment completed successfully!"
