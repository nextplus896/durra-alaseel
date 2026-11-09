#!/bin/bash
# ==============================================================================
# 🚀 Durra Alaseel - Automated Deployment Script
# ==============================================================================
# سكريبت النشر الآلي لتطبيق Laravel مع إدارة الصلاحيات، الحزم، وقاعدة البيانات.
# ==============================================================================

set -e
set -E

# ------------------------------------------------------------------------------
# 1. إعداد المتغيرات والمعلومات الأساسية (Configuration)
# ------------------------------------------------------------------------------
START_TIME=$(date +%s)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${1:-${DEPLOY_BRANCH:-main}}"
DEPLOY_USER="$(whoami)"
MAINTENANCE_SECRET="secret-token-123"
NODE_VERSION="20"

# الانتقال الفوري لمسار المشروع لضمان تنفيذ كل الأوامر في المكان الصحيح دائماً
cd "$PROJECT_DIR"

# تحديد مجموعة خادم الويب تلقائياً (www-data في Ubuntu/Debian أو nginx/apache في CentOS/RHEL)
WEB_GROUP="www-data"
if command -v getent &>/dev/null; then
    if getent group www-data &>/dev/null; then
        WEB_GROUP="www-data"
    elif getent group nginx &>/dev/null; then
        WEB_GROUP="nginx"
    elif getent group apache &>/dev/null; then
        WEB_GROUP="apache"
    fi
fi

# ------------------------------------------------------------------------------
# 2. إعداد الألوان وتوابع الطباعة المنسقة (Formatting & Logger)
# ------------------------------------------------------------------------------
if [ -t 1 ]; then
    COLOR_RESET="\033[0m"
    COLOR_BOLD="\033[1m"
    COLOR_CYAN="\033[0;36m"
    COLOR_GREEN="\033[0;32m"
    COLOR_YELLOW="\033[0;33m"
    COLOR_RED="\033[0;31m"
    COLOR_MAGENTA="\033[0;35m"
else
    COLOR_RESET=""
    COLOR_BOLD=""
    COLOR_CYAN=""
    COLOR_GREEN=""
    COLOR_YELLOW=""
    COLOR_RED=""
    COLOR_MAGENTA=""
fi

log_step() {
    echo -e "\n${COLOR_BOLD}${COLOR_CYAN}──▶ [$1] $2${COLOR_RESET}"
}

log_info() {
    echo -e "    ${COLOR_CYAN}ℹ️  $1${COLOR_RESET}"
}

log_success() {
    echo -e "    ${COLOR_GREEN}✔  $1${COLOR_RESET}"
}

log_warn() {
    echo -e "    ${COLOR_YELLOW}⚠️  $1${COLOR_RESET}"
}

log_error() {
    echo -e "    ${COLOR_RED}✖  $1${COLOR_RESET}"
}

# ------------------------------------------------------------------------------
# 3. معالجة الأخطاء وحماية وضع الصيانة (Error Handling & Traps)
# ------------------------------------------------------------------------------
on_error() {
    local exit_code="$1"
    local line_no="$2"
    echo -e "\n${COLOR_BOLD}${COLOR_RED}=================================================================${COLOR_RESET}"
    log_error "Deployment failed at line ${line_no} with exit code ${exit_code}!"
    echo -e "${COLOR_BOLD}${COLOR_RED}=================================================================${COLOR_RESET}"

    # محاولة إعادة تشغيل الموقع تلقائياً إذا فشل النشر لمنع بقاء الموقع معطلاً
    log_warn "Attempting to disable maintenance mode to restore site accessibility..."
    php artisan up 2>/dev/null || run_sudo rm -f storage/framework/down 2>/dev/null || true

    exit "${exit_code}"
}
trap 'on_error $? $LINENO' ERR

# دالة مساعدة لتشغيل أوامر sudo بأمان سواء كان المستخدم root أو غيره
run_sudo() {
    if [ "$(id -u)" -ne 0 ] && command -v sudo &>/dev/null; then
        sudo "$@"
    else
        "$@"
    fi
}

# ------------------------------------------------------------------------------
# 4. دالة إدارة المجلدات وضبط الصلاحيات (Permissions Helper)
# ------------------------------------------------------------------------------
setup_permissions() {
    local step_title="$1"
    if [ -n "$step_title" ]; then
        log_info "$step_title"
    fi

    mkdir -p bootstrap/cache \
             storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache/data \
             storage/logs \
             public/fileholder/img

    run_sudo usermod -a -G "$WEB_GROUP" "$DEPLOY_USER" 2>/dev/null || true
    run_sudo chown -R "$DEPLOY_USER:$WEB_GROUP" storage bootstrap/cache public/fileholder 2>/dev/null || chown -R "$DEPLOY_USER:$WEB_GROUP" storage bootstrap/cache public/fileholder 2>/dev/null || true
    run_sudo chmod -R 775 storage bootstrap/cache public/fileholder 2>/dev/null || chmod -R 775 storage bootstrap/cache public/fileholder 2>/dev/null || true
    run_sudo chmod -R g+s storage bootstrap/cache public/fileholder 2>/dev/null || true
}

# ==============================================================================
# بدء النشر (Deployment Execution)
# ==============================================================================
echo -e "${COLOR_BOLD}${COLOR_CYAN}=================================================================${COLOR_RESET}"
echo -e "${COLOR_BOLD}${COLOR_CYAN}         🚀 DURRA ALASEEL - AUTOMATED DEPLOYMENT SCRIPT           ${COLOR_RESET}"
echo -e "${COLOR_BOLD}${COLOR_CYAN}=================================================================${COLOR_RESET}"
echo -e " 📅 Started at   : $(date '+%Y-%m-%d %H:%M:%S')"
echo -e " 🌿 Target Branch: ${BRANCH}"
echo -e " 👤 Deploy User  : ${DEPLOY_USER}"
echo -e " 👥 Web Group    : ${WEB_GROUP}"
echo -e " 📁 Project Path : ${PROJECT_DIR}"
echo -e "${COLOR_BOLD}${COLOR_CYAN}=================================================================${COLOR_RESET}"

# 0. تجهيز وتأمين الصلاحيات فوراً في بداية السكريبت لمنع أي توقف
log_step "1/11" "🔒 Preparing initial directories and permissions..."
git config core.fileMode false || true
setup_permissions "Ensuring storage and bootstrap/cache permissions..."
log_success "Initial permissions prepared."

# 1. تفعيل وضع الصيانة (Maintenance Mode)
log_step "2/11" "🛑 Enabling maintenance mode..."
php artisan down --secret="$MAINTENANCE_SECRET" --render="errors::503" || true
log_success "Maintenance mode enabled (Bypass secret configured)."

# 2. سحب آخر التعديلات من Git
log_step "3/11" "📥 Pulling latest changes from Git (${BRANCH})..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
setup_permissions "Re-verifying permissions after git pull..."
log_success "Code updated to latest commit on origin/${BRANCH}."

# 3. تحديث حزم Composer للإنتاج (بدون حزم dev)
log_step "4/11" "📦 Installing Composer dependencies..."
COMPOSER_BIN="composer"
if ! command -v composer &>/dev/null && [ -f "composer.phar" ]; then
    COMPOSER_BIN="php composer.phar"
fi
$COMPOSER_BIN install --no-interaction --prefer-dist --optimize-autoloader --no-dev
log_success "Composer dependencies installed successfully."

# 4. التحقق من وجود Node.js & npm وتثبيتهما تلقائياً وبناء ملفات الـ Frontend
log_step "5/11" "🎨 Checking & building frontend assets..."
if [ -f "package.json" ]; then
    if ! command -v npm &>/dev/null; then
        log_warn "Node.js & npm not found. Installing automatically..."

        if ! command -v curl &>/dev/null && command -v apt-get &>/dev/null; then
            run_sudo apt-get update -y && run_sudo apt-get install -y curl || true
        fi

        # تثبيت Node.js LTS مع npm
        if command -v apt-get &>/dev/null; then
            if [ "$(id -u)" -ne 0 ] && command -v sudo &>/dev/null; then
                (curl -fsSL "https://deb.nodesource.com/setup_${NODE_VERSION}.x" | sudo -E bash -) && sudo apt-get install -y nodejs || true
            else
                (curl -fsSL "https://deb.nodesource.com/setup_${NODE_VERSION}.x" | bash -) && apt-get install -y nodejs || true
            fi
        elif command -v yum &>/dev/null; then
            if [ "$(id -u)" -ne 0 ] && command -v sudo &>/dev/null; then
                (curl -fsSL "https://rpm.nodesource.com/setup_${NODE_VERSION}.x" | sudo bash -) && sudo yum install -y nodejs || true
            else
                (curl -fsSL "https://rpm.nodesource.com/setup_${NODE_VERSION}.x" | bash -) && yum install -y nodejs || true
            fi
        elif command -v dnf &>/dev/null; then
            if [ "$(id -u)" -ne 0 ] && command -v sudo &>/dev/null; then
                (curl -fsSL "https://rpm.nodesource.com/setup_${NODE_VERSION}.x" | sudo bash -) && sudo dnf install -y nodejs || true
            else
                (curl -fsSL "https://rpm.nodesource.com/setup_${NODE_VERSION}.x" | bash -) && dnf install -y nodejs || true
            fi
        fi
    fi

    if command -v npm &>/dev/null; then
        NODE_VER=$(node -v 2>/dev/null || echo "N/A")
        NPM_VER=$(npm -v 2>/dev/null || echo "N/A")
        log_info "Building assets with Node: ${NODE_VER} | npm: ${NPM_VER}..."
        npm ci --no-audit || npm install --no-audit
        npm run build
        log_success "Frontend assets compiled successfully."
    else
        log_warn "Node.js/npm could not be installed automatically. Skipping frontend build..."
    fi
else
    log_info "No package.json found. Skipping frontend build."
fi

# 5. تشغيل الـ Migrations لقاعدة البيانات
log_step "6/11" "🗄️ Running database migrations..."
php artisan migrate --force
log_success "Database migrations executed."

# 6. مسح وإعادة بناء الكاش للأداء العالي
log_step "7/11" "⚡ Optimizing application cache..."
php artisan optimize:clear
php artisan route:cache
php artisan view:cache
log_success "Cache cleared and optimized."

# 7. التأكد من رابط التخزين storage symlink
log_step "8/11" "🔗 Verifying storage symlink..."
if [ ! -L "public/storage" ]; then
    php artisan storage:link || true
    log_success "Storage link created."
else
    log_info "Storage link already in place."
fi

# 8. ضبط الصلاحيات النهائية للمجلدات
log_step "9/11" "🔒 Finalizing directory permissions..."
setup_permissions "Applying final permission audit..."
log_success "Final permissions set."

# 9. إعادة تشغيل الـ Queue Workers (إن وجدت)
log_step "10/11" "🔄 Restarting queue workers & reloading PHP-FPM..."
php artisan queue:restart || true
log_info "Queue workers restart signal sent."

# 10. عمل Reload لـ PHP-FPM لتفريغ OPcache
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;" 2>/dev/null || echo "8.2")
log_info "Reloading PHP-FPM (PHP ${PHP_VERSION})..."
run_sudo systemctl reload "php${PHP_VERSION}-fpm" 2>/dev/null || \
run_sudo systemctl reload php-fpm 2>/dev/null || \
run_sudo service "php${PHP_VERSION}-fpm" reload 2>/dev/null || \
run_sudo service php-fpm reload 2>/dev/null || true
log_success "PHP-FPM reloaded successfully."

# 11. إيقاف وضع الصيانة وإعادة الموقع للعمل
log_step "11/11" "🚀 Bringing application back online..."
php artisan up || run_sudo rm -f storage/framework/down || true
log_success "Application is live and accessible!"

# احتساب وقت النشر الإجمالي
TOTAL_DURATION=$(( $(date +%s) - START_TIME ))

echo ""
echo -e "${COLOR_BOLD}${COLOR_GREEN}=================================================================${COLOR_RESET}"
echo -e "${COLOR_BOLD}${COLOR_GREEN}  🎉 DEPLOYMENT COMPLETED SUCCESSFULLY IN ${TOTAL_DURATION}s!  ${COLOR_RESET}"
echo -e "${COLOR_BOLD}${COLOR_GREEN}=================================================================${COLOR_RESET}"
