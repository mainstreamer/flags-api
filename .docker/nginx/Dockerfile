FROM nginx:stable-alpine3.17

RUN apk add --update \
    acl \
    shadow

RUN rm -rf /var/cache/apk/* && rm -rf /tmp/*

ADD ../.docker/nginx/nginx.conf /etc/nginx/
ADD ../.docker/nginx/host.conf /etc/nginx/conf.d/
ADD ../.docker/nginx/cert.crt /etc/ssl/cert.crt
ADD ../.docker/nginx/cert.key /etc/ssl/cert.key

RUN rm /etc/nginx/conf.d/default.conf

RUN echo -e "upstream php-upstream { server php:9000; }" > /etc/nginx/conf.d/upstream.conf

RUN getent group www-data || addgroup www-data

RUN adduser -D -g '' -G www-data www-data

RUN usermod -u 1000 www-data

CMD ["nginx"]

EXPOSE 80
EXPOSE 443
