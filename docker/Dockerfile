FROM php:8.2-apache

LABEL maintainer="Jörg Steinhäuser <wert@palindrom.de>"
LABEL description="Wertsachen-Inventarverwaltung (ValuSafe)"
LABEL version="4.3.27"

# ── System-Pakete ─────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ── PHP-Extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
        opcache

# ── Apache: mod_rewrite + mod_headers ─────────────────────────────────────────
RUN a2enmod rewrite headers

# ── PHP-Konfiguration ─────────────────────────────────────────────────────────
RUN echo "upload_max_filesize = 20M"        >> /usr/local/etc/php/conf.d/app.ini \
    && echo "post_max_size = 22M"           >> /usr/local/etc/php/conf.d/app.ini \
    && echo "max_execution_time = 120"      >> /usr/local/etc/php/conf.d/app.ini \
    && echo "memory_limit = 256M"           >> /usr/local/etc/php/conf.d/app.ini \
    && echo "display_errors = Off"          >> /usr/local/etc/php/conf.d/app.ini \
    && echo "log_errors = On"              >> /usr/local/etc/php/conf.d/app.ini \
    && echo "opcache.enable = 1"            >> /usr/local/etc/php/conf.d/app.ini \
    && echo "opcache.memory_consumption = 128" >> /usr/local/etc/php/conf.d/app.ini

# ── Apache VirtualHost ────────────────────────────────────────────────────────
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# ── Persistente Verzeichnisse anlegen ─────────────────────────────────────────
RUN mkdir -p \
        /var/www/html/upload \
        /var/www/html/documents \
        /var/www/html/backups \
        /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html

# ── App-Dateien kopieren ──────────────────────────────────────────────────────
# Der Build-Kontext ist das Projektwurzelverzeichnis (siehe docker-compose.yml:
# build.context = ..). Die Anwendung wird also direkt aus wert/ kopiert.
# Frueher stand hier COPY app/ — ein Verzeichnis, das es im Repository nie gab;
# der Build brach damit in dieser Zeile ab. Was nicht mitkopiert werden soll,
# steht in der .dockerignore im Projektwurzelverzeichnis.
COPY --chown=www-data:www-data wert/ /var/www/html/

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
