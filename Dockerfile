FROM php:8.3-apache

# Mga kailangan ng Laravel (Postgres para sa Supabase, gd para sa dompdf, etc.)
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
    nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd mbstring bcmath exif \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Gawing public/ ang root ng Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . .

# I-install ang PHP packages
RUN composer install --no-dev --optimize-autoloader --no-interaction

# I-build ang Vite assets (kung may package.json)
RUN if [ -f package.json ]; then npm install && npm run build; fi

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

# Pagsimula: i-run muna ang migrations, tapos buksan ang Apache
CMD ["sh", "-c", "php artisan migrate --force; apache2-foreground"]
