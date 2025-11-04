#!/bin/bash
set -e

# Render poskytne premennú PORT; ak tu nie je, použij default 80
: "${PORT:=80}"

# Zmeň Listen port v ports.conf (ak tam je 80)
if grep -q '^Listen ' /etc/apache2/ports.conf; then
  sed -ri "s/Listen\s+[0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
else
  echo "Listen ${PORT}" >> /etc/apache2/ports.conf
fi

# Upravi VirtualHost z *:80 na *:${PORT}
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
  sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

# Daj pozor: ak máš vlastné apache conf súbory, uprav ich podobne

# Ak index súbory chýbajú, upozorni v logu (diagnostika)
if [ ! -f /var/www/html/index.php ] && [ ! -f /var/www/html/index.html ]; then
  echo "WARNING: No index.php or index.html found in /var/www/html — Apache will return 403." >&2
fi

# Spusti apache v popredí
exec apache2 -DFOREGROUND
