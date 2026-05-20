#!/bin/sh
set -e

# Crear directorios necesarios en el volumen si no existen
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/app/livewire-tmp

chmod -R 775 /var/www/html/storage

# Crear symlink de storage
php artisan storage:link --force || true

# Ejecutar el comando principal
exec "$@"
