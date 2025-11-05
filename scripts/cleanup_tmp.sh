#!/bin/sh
# cleanup_tmp.sh
# Vymaže súbory v /var/www/html/tmp staršie ako 24 hodín (1440 minút).

TMP_DIR="/var/www/html/tmp"

if [ -d "$TMP_DIR" ]; then
  echo "Cleaning up files older than 24h in $TMP_DIR"
  # súbory staršie než 1440 minút (24h)
  find "$TMP_DIR" -type f -mmin +1440 -print -delete
  # voliteľne odstrániť prázdne adresáre
  find "$TMP_DIR" -type d -empty -delete
else
  echo "Dir $TMP_DIR does not exist, nothing to clean."
fi
