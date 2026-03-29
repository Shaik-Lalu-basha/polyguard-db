FROM php:8.2-apache

# Install Python only (NO pip install)
RUN apt-get update && apt-get install -y \
    python3 \
    libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files
COPY . /var/www/html/

# Enable Apache rewrite
RUN a2enmod rewrite

EXPOSE 80
