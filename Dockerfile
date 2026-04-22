FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY . .

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]