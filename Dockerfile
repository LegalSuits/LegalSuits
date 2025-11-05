FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

# Nainštaluj build deps + oniguruma pre mbstring + poppler-utils (pdfinfo)
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    unzip zip libzip-dev zlib1g-dev \
    libxml2-dev \
    libpng-dev libjpeg-dev \
    libonig-dev pkg-config build-essential \
    poppler-utils \
 && docker-php-ext-install zip xml mbstring \
 && apt-get remove --purge -y build-essential pkg-config \
 && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

# Skopíruj entrypoint (upraví apache config podľa PORT)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Nastavenie PHP upload limitov - uprav si podľa potreby (50M default)
RUN printf "upload_max_filesize=50M\npost_max_size=52M\nmax_execution_time=120\n" > /usr/local/etc/php/conf.d/uploads.ini

# Skopíruj kód aplikácie do document root (kopíruje obsah api do /var/www/html)
COPY api/ /var/www/html/

# Skopíruj cleanup skript (bude spúšťaný cez Render Scheduled Job)
COPY scripts/cleanup_tmp.sh /usr/local/bin/cleanup_tmp.sh
RUN chmod +x /usr/local/bin/cleanup_tmp.sh

# Zabezpeč práva pre web server
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

# Voliteľne explicitne nastav DirectoryIndex (ak by chýbalo)
RUN sed -i 's/DirectoryIndex .*/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf || true

# EXPOSE nie je nutné pre Render, ale pomáha pri lokálnom workflówe
EXPOSE 80

# Použi náš entrypoint (ten upraví conf a spustí apache)
CMD ["/usr/local/bin/docker-entrypoint.sh"]

