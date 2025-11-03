FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    unzip zip libzip-dev libxml2-dev libpng-dev libjpeg-dev \
    && docker-php-ext-install zip xml mbstring

# Composer (z iného obrazu)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# ak máš composer.json, nainštaluj závislosti
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
