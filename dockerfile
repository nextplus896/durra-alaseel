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

# Write a self-contained nginx config for Laravel.
# serversideup generates nginx.conf dynamically via s6-overlay init scripts, but that
# generation can fail when Coolify delivers env vars incorrectly. A static file here
# ensures nginx always has a valid config regardless of the init scripts.
RUN mkdir -p /etc/nginx /var/log/nginx /var/lib/nginx/tmp \
    && cat > /etc/nginx/nginx.conf << 'NGINXCONF'
user www-data;
worker_processes auto;
pid /var/run/nginx.pid;
error_log stderr warn;

events {
    worker_connections 1024;
}

http {
    default_type application/octet-stream;

    types {
        text/html                   html htm shtml;
        text/css                    css;
        text/xml                    xml rss atom;
        text/plain                  txt;
        text/javascript             js mjs;
        image/gif                   gif;
        image/jpeg                  jpeg jpg;
        image/png                   png;
        image/svg+xml               svg svgz;
        image/webp                  webp;
        image/x-icon                ico;
        image/avif                  avif;
        application/json            json;
        application/pdf             pdf;
        application/zip             zip;
        application/wasm            wasm;
        font/woff                   woff;
        font/woff2                  woff2;
        font/ttf                    ttf;
        font/otf                    otf;
        video/mp4                   mp4;
        video/webm                  webm;
        audio/mpeg                  mp3;
        audio/ogg                   ogg;
    }

    sendfile            on;
    tcp_nopush          on;
    keepalive_timeout   65;
    client_max_body_size 100m;
    fastcgi_read_timeout 300;

    server {
        listen 8080;
        root  /var/www/html/public;
        index index.php index.html;
        charset utf-8;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        error_page 404 /index.php;

        location ~ \.php$ {
            try_files $uri =404;
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            fastcgi_param SCRIPT_FILENAME    $document_root$fastcgi_script_name;
            fastcgi_param QUERY_STRING       $query_string;
            fastcgi_param REQUEST_METHOD     $request_method;
            fastcgi_param CONTENT_TYPE       $content_type;
            fastcgi_param CONTENT_LENGTH     $content_length;
            fastcgi_param SCRIPT_NAME        $fastcgi_script_name;
            fastcgi_param REQUEST_URI        $request_uri;
            fastcgi_param DOCUMENT_URI       $document_uri;
            fastcgi_param DOCUMENT_ROOT      $document_root;
            fastcgi_param SERVER_PROTOCOL    $server_protocol;
            fastcgi_param HTTPS              $https if_not_empty;
            fastcgi_param GATEWAY_INTERFACE  CGI/1.1;
            fastcgi_param SERVER_SOFTWARE    nginx;
            fastcgi_param REMOTE_ADDR        $remote_addr;
            fastcgi_param REMOTE_PORT        $remote_port;
            fastcgi_param SERVER_ADDR        $server_addr;
            fastcgi_param SERVER_PORT        $server_port;
            fastcgi_param SERVER_NAME        $server_name;
            fastcgi_param REDIRECT_STATUS    200;
            fastcgi_buffers      16 16k;
            fastcgi_buffer_size  32k;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
NGINXCONF

EXPOSE 8080

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["/init"]
