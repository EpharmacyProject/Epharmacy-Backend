#!/bin/bash

# Copy .env file if not exists
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate application key if not set
if [ -z "$(grep '^APP_KEY=' .env)" ] || [ "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" == "" ]; then
    php artisan key:generate
fi

# Clear all caches first
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Run database migrations
php artisan migrate --force

# Start Apache
exec apache2-foreground 