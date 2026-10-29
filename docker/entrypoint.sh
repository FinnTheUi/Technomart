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

# A container starts with a fresh filesystem on Render/Cloud Run. Laravel's
# default SQLite database is excluded from the image, so create it before any
# Artisan command attempts to connect. For a managed database this branch is
# skipped and the configured connection is used as-is.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    sqlite_path="${DB_DATABASE:-/var/www/html/database/database.sqlite}"

    case "$sqlite_path" in
        /*) ;;
        *) sqlite_path="/var/www/html/$sqlite_path" ;;
    esac

    mkdir -p "$(dirname "$sqlite_path")"
    touch "$sqlite_path"
    chown www-data:www-data "$sqlite_path"
    export DB_DATABASE="$sqlite_path"
fi

# Database-backed sessions, cache, and queues all need their tables before the
# first HTTP request. Multiple production processes can start together, so use
# an atomic directory lock to prevent concurrent SQLite migrations.
migration_lock="storage/framework/migration.lock"
until mkdir "$migration_lock" 2>/dev/null; do
    sleep 1
done

remove_migration_lock() {
    rmdir "$migration_lock" 2>/dev/null || true
}
trap remove_migration_lock EXIT INT TERM

php artisan migrate --force --no-interaction
remove_migration_lock
trap - EXIT INT TERM
php artisan optimize

exec "$@"
