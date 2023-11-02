FROM swiftcode/flags:php-fpm-latest

ARG KEY

RUN echo "$KEY" | base64 -d > /home/www-data/.ssh/id_rsa
RUN chmod 0600 /home/www-data/.ssh/id_rsa
RUN chown -R www-data:www-data /home/www-data/.ssh
USER www-data
WORKDIR /var/www/webapp
RUN export GIT_SSH_COMMAND="ssh -o StrictHostKeyChecking=no" && git clone git@github.com:mainstreamer/flags-api.git .
RUN cp .env.prod .env
RUN composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader --classmap-authoritative
RUN rm /home/www-data/.ssh/id_rsa
