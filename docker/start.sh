#!/bin/sh
# Boots the app on Render: listen on $PORT, cache config, run migrations, start Apache.
set -e

sed -i "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
