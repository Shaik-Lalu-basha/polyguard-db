FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

COPY requirements.txt /var/www/html/
RUN if [ -s /var/www/html/requirements.txt ]; then pip3 install -r /var/www/html/requirements.txt; fi

COPY . /var/www/html/

RUN a2enmod rewrite

EXPOSE 80
