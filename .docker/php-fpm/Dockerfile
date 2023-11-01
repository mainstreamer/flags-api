FROM swiftcode/flags:php-fpm-latest

ARG KEY

RUN echo "$KEY" > /home/www-data/.ssh/id_rsa
RUN chmod 0600 /home/www-data/.ssh/id_rsa
RUN chown -R www-data:www-data /home/www-data/.ssh
USER www-data
WORKDIR /var/www/webapp
RUN export GIT_SSH_COMMAND="ssh -o StrictHostKeyChecking=no" && git clone git@github.com:mainstreamer/flags-api.git .
RUN composer install
RUN rm /home/www-data/.ssh/id_rsa