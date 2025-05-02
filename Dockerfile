# Use PHP with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Configure Apache to listen on port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf
RUN echo "Listen 8080" >> /etc/apache2/ports.conf

# Enable Apache rewrite module
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Replace Apache config if you have a custom one
COPY docker/apache2.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generate application key if not exists
RUN if [ ! -f .env ]; then \
        cp .env.example .env && \
        php artisan key:generate; \
    fi

# Expose port 8080 to match Railway's expectations
EXPOSE 8080

# Run migrations and start Apache
CMD ["sh", "-c", "php artisan migrate --force && apache2-foreground"]
