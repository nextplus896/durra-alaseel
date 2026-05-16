FROM serversideup/php:8.4-fpm-nginx

USER root

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

# Install ext-gd (required by buglinjo/laravel-webp and phpoffice/phpspreadsheet)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && rm -rf /var/lib/apt/lists/*

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN chmod +x /var/www/html/docker-entrypoint.sh && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure PHP-FPM pool always has a valid user — serversideup generates this
# dynamically from PUID, but Coolify can deliver PUID as empty at runtime.
RUN printf '[www]\nuser = www-data\ngroup = www-data\n' \
    > /usr/local/etc/php-fpm.d/zzz-user-override.conf

ENV PUID=9999
ENV PGID=9999

EXPOSE 8080

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["/init"]
