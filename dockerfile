FROM webdevops/php-nginx:8.3

WORKDIR /app

# Copy dependency files first — composer layer only rebuilds when these change
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# Copy the rest of the application (.env, vendor/, storage/ excluded via .dockerignore)
COPY . /app

RUN composer dump-autoload --optimize --no-dev

RUN chmod +x /app/docker-entrypoint.sh
RUN chmod -R 775 storage bootstrap/cache

# NOTE: Persistent storage for /app/storage/app/public must be configured
# as a volume mount inside Coolify UI under "Persistent Storage".

ENV WEB_DOCUMENT_ROOT=/app/public

EXPOSE 80

ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord"]
