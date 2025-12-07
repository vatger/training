# Build stage for frontend assets with PHP
FROM php:8.4-alpine AS frontend

# Install Node.js and required dependencies for PHP extensions
RUN apk add --no-cache nodejs npm \
    icu-dev \
    libzip-dev \
    zip \
    curl

# Install PHP extensions
RUN docker-php-ext-install intl zip

WORKDIR /app

# Install composer and verify installation
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer \
    && composer --version

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Copy all application files
COPY . .

# Complete composer setup
RUN composer dump-autoload --optimize --no-dev

# Remove any cached bootstrap files
RUN rm -f bootstrap/cache/*.php

# Install Node.js dependencies
RUN npm ci

# Build assets
RUN npm run build

# Production stage
FROM php:8.4-apache

# Install dependencies
RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    zip \
    libpq-dev \
    libicu-dev \
    curl \
    && docker-php-ext-install intl zip pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Enable mod_rewrite
RUN a2enmod rewrite

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set the working directory
WORKDIR /var/www/html

# Install composer and verify
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

# Copy composer files first
COPY composer.json composer.lock ./

# Install project dependencies (no scripts to avoid errors before all files are copied)
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy the application code
COPY . .

# Run composer scripts now that all files are present
RUN composer dump-autoload --optimize --no-dev

# Remove any cached bootstrap files that might have been copied
RUN rm -f bootstrap/cache/*.php

# Copy built assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]