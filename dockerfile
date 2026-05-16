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

# The webdevops 10-init.sh generates the PHP-FPM upstream conf from $PHP_FPM_HOST,
# but Coolify does not propagate that env var into the supervisord init script context.
# Replace it with a hardcoded version so nginx always gets the correct upstream.
RUN printf '#!/bin/sh\nmkdir -p /opt/docker/etc/nginx/conf.d\nprintf "upstream php {\\n  server 127.0.0.1:9000;\\n}\\n" > /opt/docker/etc/nginx/conf.d/10-php.conf\n' \
    > /opt/docker/bin/service.d/nginx.d/10-init.sh \
    && chmod +x /opt/docker/bin/service.d/nginx.d/10-init.sh

RUN mkdir -p /var/log/nginx

EXPOSE 80

ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord"]
