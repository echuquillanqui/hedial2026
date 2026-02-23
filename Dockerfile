FROM php:8.2-apache

# 1. Instalar dependencias del sistema y extensiones de PHP (Postgres inclusive)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql bcmath

# 2. Habilitar mod_rewrite de Apache para Laravel
RUN a2enmod rewrite

# 3. Configurar el directorio de trabajo
WORKDIR /var/www/html
COPY . .

# 4. Instalar Composer y las librerías de tu proyecto
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 5. Dar permisos de escritura a las carpetas de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Configurar Apache para que apunte a la carpeta /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 7. Crear el script que corre Migraciones y Seeders al encender el servidor
RUN echo '#!/bin/bash\n\
php artisan migrate --force\n\
php artisan db:seed --force\n\
apache2-foreground' > /usr/local/bin/start.sh

# Dar permisos de ejecución al script
RUN chmod +x /usr/local/bin/start.sh

# 8. Indicar el puerto
EXPOSE 80

# 9. Comando final: ejecutar el script de inicio
CMD ["/usr/local/bin/start.sh"]