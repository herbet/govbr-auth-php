FROM php:8.1-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libcurl4-openssl-dev \
    pkg-config \
    && docker-php-ext-install curl

# Habilita Rewrite no Apache e ativa .htaccess
RUN a2enmod rewrite && \
    sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto ANTES de rodar o Composer
COPY composer.json ./
RUN composer install

# Copiar demais arquivos (src/, public/)
COPY . .

# Ajustar permissões
RUN chown -R www-data:www-data /var/www/html

# Configurar Apache para apontar para o diretório /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
