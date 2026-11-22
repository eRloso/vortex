# ==========================================================================
#  Vortex · Imagen de producción (EthioDeploy u otro hosting con Docker)
#  PHP 8.3 + Apache, sirviendo solo la carpeta public/
# ==========================================================================
FROM php:8.3-apache

# Extensión para conectarse a MySQL / MariaDB
RUN docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# EthioDeploy indica el puerto en la variable PORT (por defecto 3000)
ENV PORT=3000 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public \
    APP_DEBUG=false

# Apache escucha en ${PORT} y publica solo public/
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri 's!^Listen 80$!Listen ${PORT}!' /etc/apache2/ports.conf \
    && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!' /etc/apache2/sites-available/000-default.conf \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/storage

EXPOSE 3000
CMD ["apache2-foreground"]
