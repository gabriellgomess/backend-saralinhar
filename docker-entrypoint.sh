#!/bin/sh
set -e

# Garante que os diretórios necessários existam no storage
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Cria o link simbólico de storage
php artisan storage:link --force

# Executa migrações e limpa cache se for o container web
if [ "$1" = "apache2-foreground" ]; then
    echo "Executando migrações de banco de dados..."
    php artisan migrate --force
    
    echo "Otimizando cache do Laravel para produção..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
