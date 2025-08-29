FROM php:8.4.8-fpm

RUN apt-get clean && apt-get update && apt-get install -y \
    libreadline-dev \
    libsodium-dev \
    libargon2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libxml2-dev \
    libwebp-dev \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    bison \
    re2c \
    autoconf \
    build-essential \
    libtool \
    pkg-config \
    git

# Install core PHP extensions
RUN docker-php-ext-configure gd --with-webp --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install xml curl mbstring intl gettext xmlwriter dom \
    && docker-php-ext-install zip \
    && docker-php-ext-configure pdo_mysql \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-enable pdo_mysql \
    && docker-php-ext-install mysqli

# Install PECL extensions
RUN pecl install apcu \
    && docker-php-ext-enable apcu \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && pecl install redis \
    && docker-php-ext-enable redis
