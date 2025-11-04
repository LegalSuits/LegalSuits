# in Dockerfile replace the apt-get line with this:
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    unzip zip \
    libzip-dev zlib1g-dev \
    libxml2-dev \
    libpng-dev libjpeg-dev \
    libonig-dev pkg-config build-essential \
 && docker-php-ext-install zip xml mbstring \
 && apt-get remove --purge -y build-essential pkg-config \
 && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*
