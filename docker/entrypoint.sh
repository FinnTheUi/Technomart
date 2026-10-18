#!/bin/sh
set -eu

cloud_run_port="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${cloud_run_port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${cloud_run_port}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

exec "$@"
