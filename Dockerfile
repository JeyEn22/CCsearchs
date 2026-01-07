FROM php:8.2-apache
RUN apt-get update && apt-get install -y \
    imagemagick \
    libmagickwand-dev \
    ghostscript \
    && pecl install imagick \
    && docker-php-ext-enable imagick
RUN a2enmod rewrite
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
