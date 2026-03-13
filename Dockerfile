FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install zip intl

# Installation des extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql opcache

# Installation de l'extension MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Configuration PHP pour le développement
COPY docker/php.ini "$PHP_INI_DIR/php.ini"

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configuration d'Apache
RUN a2enmod rewrite headers setenvif
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Création des répertoires de logs
RUN mkdir -p /var/log/apache2 \
    && touch /var/log/apache2/error.log \
    && touch /var/log/apache2/access.log \
    && chown -R www-data:www-data /var/log/apache2

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Permissions pour Apache
RUN chown -R www-data:www-data /var/www/html

# Exposition du port
EXPOSE 80

# Le CMD par défaut de l'image apache est correct, pas besoin de le redéfinir
