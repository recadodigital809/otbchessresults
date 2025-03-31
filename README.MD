# Acceder al contenedor
docker exec -it otbchessresults bash

# Instalar el paquete phpdotenv via Composer
docker-compose exec php-app composer require vlucas/phpdotenv

# Instalar mediante Composer (dentro del contenedor):
docker-compose exec php-app composer require google/auth

docker-compose exec php-app composer require league/oauth2-client


# Verifica que los paquetes están instalados
docker-compose exec php-app composer show

# Dentro del contenedor:
cd /var/www/html
composer install --no-dev --optimize-autoloader
exit

# RUN
docker-compose up -d --build


# Restart
docker-compose down -v && docker-compose up -d --build

# Git Command
git add .

git commit -m "Add new feature"

git push

git push origin HEAD:master

