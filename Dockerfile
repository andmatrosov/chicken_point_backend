FROM php:8.3-apache

ARG APACHE_DOCUMENT_ROOT=/var/www/html/public

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --prefer-dist

COPY . .

RUN set -eux; \
    mkdir -p \
        /data/geoip \
        storage/app/geoip \
        storage/app/public \
        storage/api-docs \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache; \
    chmod -R ug+rwx storage bootstrap/cache; \
    APP_ENV=local \
    APP_DEBUG=false \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    APP_URL=http://localhost \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    GAME_SIGNATURE_ENABLED=false \
    php artisan package:discover --ansi; \
    APP_ENV=local \
    APP_DEBUG=false \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    APP_URL=http://localhost \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    GAME_SIGNATURE_ENABLED=false \
    php artisan l5-swagger:generate; \
    APP_ENV=local \
    APP_DEBUG=false \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    APP_URL=http://localhost \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    GAME_SIGNATURE_ENABLED=false \
    php artisan optimize:clear --ansi

EXPOSE 80

CMD ["apache2-foreground"]
