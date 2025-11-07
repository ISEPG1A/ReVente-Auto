# Base image with Apache and PHP
FROM php:8.2-apache

# Enable Apache modules and install PHP extensions commonly needed
RUN a2enmod rewrite \
    && apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/*

# Set DocumentRoot to the "public" folder of your project
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Update Apache configuration to use the new DocumentRoot and allow .htaccess overrides
RUN sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' /etc/apache2/apache2.conf \
    && printf "<Directory /var/www/html/public>\n\tOptions Indexes FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n" > /etc/apache2/conf-available/project.conf \
    && a2enconf project \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable PHP error display for debugging (disable in production)
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_log = /var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/errors.ini

# Copy project files into the container
# The repo root for App Platform should be the same folder containing this Dockerfile
COPY . /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create a simple health check endpoint
RUN echo "<?php http_response_code(200); echo 'OK'; ?>" > /var/www/html/public/health.php

# Expose Apache default port (App Platform can be configured to use 80 as HTTP port)
EXPOSE 80

# Start Apache in foreground
# Script d'entrée pour ajuster permissions du volume uploads avant de lancer Apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
