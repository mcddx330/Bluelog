FROM php:8.4-fpm

# install system packages
RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libonig-dev libxml2-dev libzip-dev curl \
    && docker-php-ext-install pdo_mysql mbstring gd zip \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
