FROM php:8.2-cli

# Install the PDO MySQL extension required for database connectivity
RUN docker-php-ext-install pdo_mysql

# Set the working directory
WORKDIR /var/www/html

# Copy all application files into the container
COPY . .

# Expose Railway's standard port
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080"]
