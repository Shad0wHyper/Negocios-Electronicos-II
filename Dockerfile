FROM php:8.2-apache

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Instalar dependencias del sistema y Composer
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*


# Instalar extensión ZIP
RUN docker-php-ext-install zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar el código de la aplicación al contenedor
COPY . /var/www/html/

# Instalar dependencias de PHP vía Composer
WORKDIR /var/www/html/
RUN composer install --no-dev --optimize-autoloader

# Ajustar permisos para Apache
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 8080 (Cloud Run lo requiere)
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
EXPOSE 8080


