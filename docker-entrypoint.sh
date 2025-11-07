#!/bin/sh
set -e

# Correction automatique des permissions du dossier uploads (volume persistant)
UPLOADS_DIR="${UPLOADS_DIR:-/var/www/html/public/uploads}"
if [ -d "$UPLOADS_DIR" ]; then
  echo "[entrypoint] Fix permissions on $UPLOADS_DIR"
  chown -R www-data:www-data "$UPLOADS_DIR" || echo "[entrypoint] WARN: chown failed"
  chmod -R 755 "$UPLOADS_DIR" || echo "[entrypoint] WARN: chmod failed"
else
  echo "[entrypoint] Dossier $UPLOADS_DIR absent (sera peut-être créé plus tard)"
fi

exec apache2-foreground
