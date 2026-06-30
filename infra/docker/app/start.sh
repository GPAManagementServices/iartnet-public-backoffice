#!/bin/sh
set -eu

echo "[entrypoint] Clearing Laravel caches"
php artisan optimize:clear

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "[entrypoint] Running migrations"
  php artisan migrate --force
else
  echo "[entrypoint] Skipping migrations (RUN_MIGRATIONS=${RUN_MIGRATIONS:-false})"
fi

echo "[entrypoint] Starting php-fpm"
exec docker-php-entrypoint php-fpm
