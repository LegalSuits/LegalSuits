#!/bin/sh
set -e

# SOURCE apache envvars so variables like APACHE_RUN_DIR are defined
if [ -f /etc/apache2/envvars ]; then
  # use dot to source so env vars become available in this shell
  . /etc/apache2/envvars
fi

# Ensure runtime dir exists (APACHE_RUN_DIR usually /var/run/apache2)
RUNDIR="${APACHE_RUN_DIR:-/var/run/apache2}"
mkdir -p "$RUNDIR"
chown -R "${APACHE_RUN_USER:-www-data}:${APACHE_RUN_GROUP:-www-data}" "$RUNDIR" || true

# If Render (alebo iný) sets PORT env, replace Listen 80 -> Listen $PORT
if [ -n "$PORT" ]; then
  # modify ports.conf
  if [ -f /etc/apache2/ports.conf ]; then
    sed -i "s/Listen [0-9]\+/Listen ${PORT}/" /etc/apache2/ports.conf || true
  fi
  # try to update any enabled vhost files (if present)
  for f in /etc/apache2/sites-enabled/*.conf; do
    [ -f "$f" ] || continue
    sed -i "s/<VirtualHost \*:[0-9]\+>/<VirtualHost *:${PORT}>/g" "$f" || true
  done
fi

# Finally exec apache in foreground
exec apache2 -D FOREGROUND
