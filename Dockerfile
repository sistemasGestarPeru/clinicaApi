FROM php:8.2.17-fpm-alpine

RUN apk add --no-cache nginx wget

RUN mkdir -p /run/nginx

COPY docker/nginx.conf /etc/nginx/nginx.conf

RUN mkdir -p /app
COPY . /app
COPY ./src /app

RUN docker-php-ext-install pdo pdo_mysql

RUN sh -c "wget http://getcomposer.org/composer.phar && chmod a+x composer.phar && mv composer.phar /usr/local/bin/composer"
RUN cd /app && \
    /usr/local/bin/composer install --no-dev

RUN chown -R www-data: /app

# Define las variables de entorno para la conexión a Cloud SQL
ENV DB_CONNECTION=mysql
ENV DB_HOST=34.176.138.52
ENV DB_PORT=3306
ENV DB_DATABASE=bd_gestar
ENV DB_USERNAME=root
ENV DB_PASSWORD='({Bjh+.h%uR`""JA'

CMD sh /app/docker/startup.sh
