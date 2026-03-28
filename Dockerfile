FROM php:8.2-apache

# Install the PDO MySQL extension required for database connectivity
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite for proper URL routing
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy all application files into the container
COPY . .

# Expose standard HTTP port
EXPOSE 80
