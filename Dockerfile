FROM php:8.2-apache

# Install MySQL and SSL extensions required for PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite module
RUN a2enmod rewrite

# Copy project files into web root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Fix permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Expose HTTP port
EXPOSE 80

CMD ["apache2-foreground"]