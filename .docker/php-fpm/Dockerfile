# ==========================================
# Base stage - shared foundation
# ==========================================
FROM php:8.4-fpm-alpine AS base

# Copy the PHP extension installer (uses pre-built binaries when available)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install all extensions in one command - MUCH faster
RUN install-php-extensions \
    pdo_mysql \
    intl \
    opcache \
    gd \
    xml \
    mbstring \
    zip \
    redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ==========================================
# Development stage
# ==========================================
FROM base AS development

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apk add --no-cache git curl wget vim bash

RUN { \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 2M'; \
    echo 'post_max_size = 8M'; \
    echo 'max_execution_time = 30'; \
    echo 'date.timezone = UTC'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.enable_cli = 1'; \
    echo 'opcache.validate_timestamps = 1'; \
    echo 'opcache.revalidate_freq = 0'; \
    echo 'session.save_handler = redis'; \
    echo 'session.save_path = "tcp://redis:6379"'; \
    echo 'display_errors = On'; \
    echo 'error_reporting = E_ALL'; \
    echo 'log_errors = On'; \
} > /usr/local/etc/php/conf.d/custom.ini

RUN deluser www-data 2>/dev/null || true && \
    addgroup -g ${GROUP_ID} www-data && \
    adduser -u ${USER_ID} -G www-data -s /bin/sh -D www-data && \
    chown -R www-data:www-data /var/www

COPY .docker/php-fpm/docker-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    chown www-data:www-data /entrypoint.sh

USER www-data

RUN wget https://get.symfony.com/cli/installer -O - | bash || true && \
    if [ -f ~/.symfony5/bin/symfony ]; then \
        mkdir -p ~/bin && mv ~/.symfony5/bin/symfony ~/bin/symfony; \
    fi

ENV PATH="/home/www-data/bin:${PATH}"

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ==========================================
# Builder stage
# ==========================================
FROM base AS builder

RUN apk add --no-cache git

RUN { \
    echo 'memory_limit = 256M'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.enable_cli = 0'; \
    echo 'session.save_handler = redis'; \
    echo 'session.save_path = "tcp://redis:6379"'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Copy composer files for layer caching
COPY --chown=www-data:www-data ./app/composer.json ./app/composer.lock* ./app/symfony.lock* ./

# USE BUILDKIT CACHE for Composer - HUGE speedup
RUN --mount=type=cache,target=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer \
    composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

# Copy application code
COPY --chown=www-data:www-data app/ .

RUN composer dump-autoload --optimize --classmap-authoritative

# Skip cache warmup - do it at runtime instead (saves build time)
RUN chown -R www-data:www-data var/

# ==========================================
# Production stage
# ==========================================
FROM base AS production

ARG USER_ID=1000
ARG GROUP_ID=1000

# Match UIDs in production too
RUN deluser www-data 2>/dev/null || true && \
    addgroup -g ${GROUP_ID} www-data && \
    adduser -u ${USER_ID} -G www-data -s /bin/sh -D www-data

RUN { \
    echo 'memory_limit = 256M'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 256'; \
    echo 'opcache.validate_timestamps = 0'; \
    echo 'session.save_handler = redis'; \
    echo 'session.save_path = "tcp://redis.flaux.svc.cluster.local:6379"'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Copy from builder
COPY --from=builder --chown=www-data:www-data /var/www/html ./

# Remove dev files
RUN rm -rf tests/ .git/ .github/ .env.local .env.*.local \
    docker-compose.yml Dockerfile Makefile *.md phpunit.xml* \
    /usr/local/bin/composer \
    && mkdir -p var/cache var/log \
    && bin/console cache:clear --env=prod --no-warmup \
    && php bin/console cache:warmup --env=prod \
    && chown -R www-data:www-data var/

# Add entrypoint
COPY .docker/php-fpm/docker-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV APP_ENV=prod APP_DEBUG=0

# Warmup cache as root before switching to www-data
#RUN php bin/console cache:clear --no-warmup \
#    && php bin/console cache:warmup

USER www-data
EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]