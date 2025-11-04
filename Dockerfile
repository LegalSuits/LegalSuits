# základný obraz
FROM php:8.2-apache

# neinteraktívne apt výzvy pri build-e
ENV DEBIAN_FRONTEND=noninteractive

# nainštalujeme závislosti potrebné pre rozšírenia (vrátane oniguruma pre mbstring)
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    unzip zip libzip-dev zlib1g-dev \
    libxml2-dev \
    libpng-dev libjpeg-dev \
    libonig-dev pkg-config build-essential \
 && docker-php-ext-install zip xml mbstring \
 # odstránime build-time nástroje aby bol image menší
 && apt-get remove --purge -y build-essential pkg-config \
 && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

# (ďalšie voliteľné kroky: skopírovať kód, nastaviť Apache atď.)
# COPY . /var/www/html
# RUN chown -R www-data:www-data /var/www/html
