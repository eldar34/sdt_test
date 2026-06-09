# --- 1. Используем официальный образ PHP 8.4 с FPM и Alpine ---
FROM php:8.4-fpm-alpine

ARG PHP_UID=1000
ARG PHP_GID=1000

# --- 2. Установка необходимых системных зависимостей ---
RUN apk update && apk add --no-cache \
    bash \
    postgresql-dev \
    git \
    curl \
    unzip \
    tzdata \
    shadow


# --- 3. Изменение прав ---
RUN usermod -u ${PHP_UID} www-data && groupmod -g ${PHP_GID} www-data

# --- 4. Установка расширений PHP ---
RUN docker-php-ext-install -j$(nproc) pdo_pgsql pgsql opcache

# --- 5. Конфигурация PHP ---
COPY ./conf/custom-settings.ini /usr/local/etc/php/conf.d/custom-settings.ini

# --- 6. Установка Composer (v2.8+) ---
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# --- 7. Очистка временных файлов кэша apk ---
RUN rm -rf /var/cache/apk/* /tmp/*

WORKDIR /var/www/app

EXPOSE 9000

CMD ["php-fpm", "-F"]

