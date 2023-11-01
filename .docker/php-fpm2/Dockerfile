#FROM php:7.2-fpm-alpine
# FROM php:7.3.6-fpm-alpine3.9
#FROM php:7.4-fpm-alpine3.11
# FROM php:8.1-fpm
#FROM php:8.1.8RC1-fpm-alpine3.15
# ORIGINAL BASE IMAGE -


#    intl
#pcntl \
#    bcmath
#json \ always available since 8
FROM php:8.2.1-fpm-alpine3.17 as build

#ARG PROJECT_PATH
#ARG CI_COMMIT_SHORT_SHA

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
     postgresql-dev \
     mc \
     openssl \
     mysql-client \
     libpng-dev \
     grep \
     git \
     tcpdump \
     libzip-dev

RUN docker-php-ext-configure pcntl --enable-pcntl

RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    calendar \
    zip \
    intl
#    redis
#    mbstring

RUN apk add --no-cache $PHPIZE_DEPS \
   && apk del --purge autoconf g++ make \
   && apk add --no-cache --update rabbitmq-c-dev \
    && apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install -o -f amqp \
    && docker-php-ext-enable amqp \
    && apk del .phpize-deps


RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
        && pecl install redis \
        && docker-php-ext-enable redis.so



#   && pecl install xdebug \
#   && docker-php-ext-enable xdebug \

# RUN apk add --no-cache $PHPIZE_DEPS \
#    && pecl install xdebug-2.7.2 \
#    && docker-php-ext-enable xdebug \
#    && apk del --purge autoconf g++ make

COPY .docker/php-fpm/php.ini /usr/local/etc/php/php.ini

#RUN mkdir -p /var/log/php
#RUN touch /var/log/php/xdebug.log

RUN rm -rf /var/cache/apk/* && rm -rf /tmp/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

RUN groupmod -g 1000 www-data

RUN usermod -u 1000 www-data

RUN mkdir -p /var/www/webapp
# RUN mkdir -p $PROJECT_PATH
# RUN #mkdir -p /builds/mainstreamer/wfp

#RUN chown 1000:1000 /var/log/php/xdebug.log

#COPY . $PROJECT_PATH
#COPY . .
#COPY . /builds/shared/$CI_COMMIT_SHORT_SHA
#COPY . /builds/mainstreamer/wfp

#RUN chown -R 1000:1000 $PROJECT_PATH
RUN chown -R 1000:1000 /var/www/webapp
#RUN #chown -R 1000:1000 /builds/mainstreamer/wfp

RUN useradd -g root www-data & groups

USER www-data

WORKDIR /var/www/webapp
#WORKDIR $PROJECT_PATH
#WORKDIR /builds/mainstreamer/wfp

RUN composer install

RUN ls -la .

RUN pwd

EXPOSE 9000



# FROM php:8.1-fpm

# RUN apt-get update -yqq && \
#     apt-get install -yqq \
#     git \
#     curl \
#     zip \
#     unzip \
#     gzip \
#     libzip-dev \
#     libicu-dev \
#     nano



# RUN docker-php-ext-install intl pdo pdo_mysql zip opcache bcmath

# RUN pecl install xdebug && docker-php-ext-enable xdebug opcache

# RUN echo "opcache.max_accelerated_files = 20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
#     php composer-setup.php && \
#     php -r "unlink('composer-setup.php');" && \
#     mv composer.phar /usr/local/bin/composer && \
#     chmod +x /usr/local/bin/composer

# WORKDIR /app
