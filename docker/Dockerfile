FROM php:8.3-apache
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libwebp-dev libzip-dev cron \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install pdo_mysql gd zip \
 && a2enmod rewrite headers && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/config /var/www/html/storage /var/www/html/public/uploads \
 && echo "* * * * * www-data php /var/www/html/bin/cron.php >> /var/www/html/storage/logs/cron.log 2>&1" > /etc/cron.d/matjar
CMD ["bash", "-c", "cron && apache2-foreground"]
