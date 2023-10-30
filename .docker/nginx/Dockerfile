#FROM nginx:alpine
FROM nginx:stable-alpine3.17

#ARG DOCKER_PHP_CONTAINER_NAME
#ARG PROJECT_PATH

RUN apk add --update \
    acl \
    shadow \
    nano

RUN rm -rf /var/cache/apk/* && rm -rf /tmp/*

ADD .docker/nginx/nginx.conf /etc/nginx/
ADD .docker/nginx/host.conf /etc/nginx/conf.d/
#RUN sed -i 's/PROJECT_PATH/(echo $PROJECT_PATH)/g' /etc/nginx/conf.d/host.conf
#RUN sed -i 's/PROJECT_PATH/(echo $PROJECT_PATH)/g' /etc/nginx/conf.d/host.conf
ADD .docker/nginx/cert.crt /etc/ssl/cert.crt
ADD .docker/nginx/cert.key /etc/ssl/cert.key

RUN mkdir -p /var/www/webapp
#RUN mkdir -p $PROJECT_PATH
ADD public /var/www/webapp/public
#ADD public $PROJECT_PATH/public

RUN rm /etc/nginx/conf.d/default.conf

#RUN echo -e "upstream php-upstream { server $DOCKER_PHP_CONTAINER_NAME:9000; }" > /etc/nginx/conf.d/upstream.conf
RUN echo -e "upstream php-upstream { server php:9000; }" > /etc/nginx/conf.d/upstream.conf

RUN getent group www-data || addgroup www-data

RUN adduser -D -g '' -G www-data www-data

RUN usermod -u 1000 www-data

CMD ["nginx"]

EXPOSE 80
EXPOSE 443