FROM webdevops/php-nginx:8.4

WORKDIR /app

COPY . /app

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN chmod +x /app/docker-entrypoint.sh
RUN chmod -R 775 storage bootstrap/cache

ENV WEB_DOCUMENT_ROOT=/app/public
ENV PHP_FPM_HOST=127.0.0.1
ENV PHP_FPM_PORT=9000

RUN mkdir -p /var/log/nginx

EXPOSE 80

ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord"]
