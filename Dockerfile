FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Copy requirements if exists
COPY requirements.txt /var/www/html/
RUN if [ -s /var/www/html/requirements.txt ]; then pip3 install --break-system-packages -r /var/www/html/requirements.txt; fi

# Copy project files
COPY . /var/www/html/

# Enable Apache rewrite
RUN a2enmod rewrite

EXPOSE 80
