FROM serversideup/php:8.4-fpm-nginx

USER root

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN chmod +x /var/www/html/docker-entrypoint.sh && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["/init"]
