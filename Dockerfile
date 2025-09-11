# Gunakan base image PHP-FPM Alpine
FROM php:8.3-fpm-alpine

# Install ekstensi yang dibutuhkan Laravel + docker-cli
RUN apk add --no-cache \
    icu-dev oniguruma-dev libzip-dev git bash libpng-dev jpeg-dev freetype-dev \
    docker-cli \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql zip intl gd bcmath \
 && rm -rf /var/cache/apk/*

# Copy Composer dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Tambahkan konfigurasi upload dan eksekusi PHP
RUN echo "upload_max_filesize=1024M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=1024M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_input_time=300" >> /usr/local/etc/php/conf.d/uploads.ini

# Set direktori kerja
WORKDIR /var/www/html

# Salin file composer dulu agar layer cache bisa digunakan untuk install dependencies
COPY composer.json composer.lock ./

# Install dependency (termasuk dev dependencies)
RUN composer install --no-scripts --no-autoloader

# Salin semua file project kecuali yang diabaikan oleh `.dockerignore`
COPY . .

# Jalankan autoload dan script composer setelah semua file tersalin
RUN composer dump-autoload && \
    chown -R www-data:www-data storage bootstrap/cache

# Expose port untuk PHP-FPM
EXPOSE 9000

# Entry point untuk menjalankan perintah otomatis saat container start
CMD sh -c "\
    php artisan migrate --force && \
    mkdir -p storage/app/public/uploads storage/app/public/videos storage/app/public/images && \
    php artisan storage:link && \
    php-fpm"
