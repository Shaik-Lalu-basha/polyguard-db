FROM php:8.2-apache

# Install Python
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip 
    libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Install Python packages
COPY requirements.txt /var/www/html/
RUN pip3 install -r /var/www/html/requirements.txt

# Copy project files
COPY . /var/www/html/

# Enable Apache mod_rewrite
RUN a2enmod rewrite

EXPOSE 80
