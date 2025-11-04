FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

# Nainštaluj build deps + oniguruma pre mbstring
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    unzip zip libzip-dev zlib1g-dev \
    libxml2-dev \
    libpng-dev libjpeg-dev \
    libonig-dev pkg-config build-essential \
 && docker-php-ext-install zip xml mbstring \
 && apt-get remove --purge -y build-essential pkg-config \
 && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

# Vytvor entrypoint skript, ktorý prepíše Apache port na $PORT
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Skopíruj kód aplikácie do document root
# Ak chceš ignorovať niektoré súbory, rieš to v .dockerignore
COPY . /var/www/html

# Nastav vlastníka
RUN chown -R www-data:www-data /var/www/html

# Voliteľne explicitne nastav DirectoryIndex (ak by chýbalo)
RUN sed -i 's/DirectoryIndex .*/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf || true

# EXPOSE nie je nutné pre Render, ale pomáha pri lokálnom workflówe
EXPOSE 80

# Použi náš entrypoint (ten upraví conf a spustí apache)
CMD ["/usr/local/bin/docker-entrypoint.sh"]
