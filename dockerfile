# ── Stage 1: Build frontend assets ──
FROM node:22.18-alpine3.22 AS node_build

RUN apk update && apk upgrade --no-cache

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install && npm cache clean --force
COPY . .
RUN npm run build && npm cache clean --force && rm -rf /root/.npm /tmp/*

# ── Stage 2: PHP-FPM ──
FROM php:8.4.10-fpm-alpine3.21

RUN apk update && apk upgrade --no-cache

RUN apk add --no-cache \
    mysql-client \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    libwebp \
    libxml2 \
    oniguruma \
    icu-libs \
    curl \
    linux-headers \
    imagemagick

RUN apk add --no-cache --virtual .build-deps \
    libwebp-dev \
    libzip-dev \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    curl-dev \
    autoconf \
    build-base \
    imagemagick-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    mbstring \
    xml \
    simplexml \
    bcmath \
    curl \
    zip \
    intl \
    gd \
    soap \
    opcache \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apk del .build-deps \
    && rm -rf /tmp/pear /tmp/* /usr/local/include/php/ext/*

RUN apk del linux-headers 2>/dev/null || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --no-scripts \
    && composer clear-cache

COPY . .
COPY --from=node_build /app/public/build public/build

RUN addgroup -g 1000 -S appuser && adduser -u 1000 -S appuser -G appuser

RUN chown -R appuser:appuser \
    storage \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "allow_url_fopen=Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "allow_url_include=Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.cookie_httponly=1" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.cookie_samesite=Lax" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.use_strict_mode=1" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/security.ini

USER appuser

EXPOSE 9000
CMD ["php-fpm"]
