FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar entrypoint dev
COPY docker/php/entrypoint-dev.sh /usr/local/bin/entrypoint-dev.sh
RUN chmod +x /usr/local/bin/entrypoint-dev.sh

# Directorio de trabajo en /var/www/html/mrp
WORKDIR /var/www/html/mrp

# Configurar uploads
RUN echo "upload_max_filesize = 100M\npost_max_size = 100M" > /usr/local/etc/php/conf.d/uploads.ini

# Configurar PHP-FPM para escuchar en TCP
RUN echo "[global]" > /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "daemonize = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "[www]" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "listen = 0.0.0.0:9000" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "user = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "group = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm.max_children = 5" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf

ENTRYPOINT ["entrypoint-dev.sh"]
