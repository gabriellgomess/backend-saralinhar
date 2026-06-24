FROM php:8.3-apache

# Instala utilitários do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões do PHP necessárias para o Laravel e Imagick usando o php-extension-installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd zip opcache imagick

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Altera o DocumentRoot do Apache para a pasta public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto Laravel para dentro do container
COPY . /var/www/html

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instala dependências do composer (otimizado para produção, excluindo dependências de desenvolvimento)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copia o script de entrypoint e torna executável
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expõe a porta padrão 80 do Apache
EXPOSE 80

# Registra o entrypoint e inicia o Apache
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
