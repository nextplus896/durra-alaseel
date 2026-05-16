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
ENV NGINX_CLIENT_MAX_BODY_SIZE=100m

# Our custom ENTRYPOINT replaces the webdevops one, so their envsubst template
# processing never runs. Hardcode all nginx conf files that contain ${...} placeholders
# so nginx gets valid config regardless of environment variable propagation.

# 1. PHP-FPM upstream (was using $PHP_FPM_HOST)
RUN printf '#!/bin/sh\nmkdir -p /opt/docker/etc/nginx/conf.d\nprintf "upstream php {\\n  server 127.0.0.1:9000;\\n}\\n" > /opt/docker/etc/nginx/conf.d/10-php.conf\n' \
    > /opt/docker/bin/service.d/nginx.d/10-init.sh \
    && chmod +x /opt/docker/bin/service.d/nginx.d/10-init.sh

# 2. vhost common directives (was using $NGINX_CLIENT_MAX_BODY_SIZE)
RUN printf 'client_max_body_size 100m;\n' \
    > /opt/docker/etc/nginx/vhost.common.d/10-general.conf

RUN mkdir -p /var/log/nginx

EXPOSE 80

ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord"]
