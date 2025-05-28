#!/bin/bash
set -e

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Creating database.sqlite file..."
mkdir -p /var/www/html/storage/database
touch /var/www/html/storage/database/database.sqlite
chmod 666 /var/www/html/storage/database/database.sqlite


echo "Running migrations..."
php artisan migrate --force

# echo "Seeding database..."
# php artisan db:seed --force

echo "Deployment complete!"