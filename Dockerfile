# Use PHP CLI with required extensions
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy only Composer files first to cache dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Copy the rest of the project files
COPY . .

# Finish Composer installation
RUN composer dump-autoload --optimize

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose the port (Railway assigns dynamically via $PORT)
EXPOSE $PORT

# Start Laravel server, cache configs/routes/views and run migrations at runtime
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache || true && php artisan view:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT"]