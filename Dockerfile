# Use PHP CLI with required extensions
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Run migrations before starting the server
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]