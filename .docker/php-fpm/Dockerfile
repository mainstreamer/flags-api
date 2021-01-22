FROM php:7.4-fpm-alpine3.11

RUN echo http://nl.alpinelinux.org/alpine/edge/testing >> /etc/apk/repositories; \
    echo http://dl-2.alpinelinux.org/alpine/edge/community >> /etc/apk/repositories; \
    echo http://dl-3.alpinelinux.org/alpine/edge/community >> /etc/apk/repositories; \
    echo http://dl-4.alpinelinux.org/alpine/edge/community >> /etc/apk/repositories; \
    echo http://dl-5.alpinelinux.org/alpine/edge/community >> /etc/apk/repositories

RUN apk add --update \
     acl \
     sudo \
     bash \
     shadow \
     mc \
     openssl \
     mysql-client \
     libpng-dev \
     grep \
     git \
     tcpdump \
     libzip-dev 

RUN docker-php-ext-install pdo pdo_mysql \
    calendar \
    zip \
    pcntl

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.9.6 \
    && docker-php-ext-enable xdebug \
    && apk del --purge autoconf g++ make

ADD .docker/php-fpm/php.ini /usr/local/etc/php/php.ini

RUN mkdir -p /var/log/php
RUN touch /var/log/php/xdebug.log

RUN rm -rf /var/cache/apk/* && rm -rf /tmp/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

RUN groupmod -g 1000 www-data

RUN usermod -u 1000 www-data

RUN mkdir -p /var/www/webapp

RUN chown 1000:1000 /var/www/webapp

RUN chown 1000:1000 /var/log/php/xdebug.log

ADD . /var/www/webapp

## HANDLE PRESETS
ADD .docker/presets/secrets/dev.decrypt.private.php /var/www/webapp/config/secrets/dev
ADD .docker/presets/secrets/prod.decrypt.private.php /var/www/webapp/config/secrets/prod
ADD .docker/presets/db/db.sql /var/www/webapp

WORKDIR /var/www/webapp

RUN useradd -g root www-data & groups

RUN composer install --no-suggest && \
    composer clear-cache
    
RUN mkdir -p config/jwt
RUN mkdir tests
RUN openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:123123123
RUN openssl pkey -in config/jwt/private.pem -passin pass:123123123 -out config/jwt/public.pem -pubout 

RUN chown -R 1000:1000 /var/www/webapp/var /var/www/webapp/tests /var/www/webapp/public 
RUN chown -R www-data:1000 /var/www/webapp/config

USER www-data

RUN ls -la .

EXPOSE 9000
EXPOSE 9001
