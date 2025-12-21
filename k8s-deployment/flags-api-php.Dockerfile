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

USER www-data

RUN wget https://get.symfony.com/cli/installer -O - | bash || true && \
    if [ -f ~/.symfony5/bin/symfony ]; then \
        mkdir -p ~/bin && mv ~/.symfony5/bin/symfony ~/bin/symfony; \
    fi

ENV PATH="/home/www-data/bin:${PATH}"

EXPOSE 9000
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
COPY --chown=www-data:www-data ./composer.json ./composer.lock* ./symfony.lock* ./

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
COPY --chown=www-data:www-data . .

# === COPY PUBLIC KEY FROM HQAUTH PROJECT ===
# This assumes you're building with a build context that includes the HQAUTH public key
# Example: docker build --build-arg HQAUTH_PUBLIC_KEY="$(cat /path/to/hqauth/config/jwt/public.pem)" .
# OR mount it during build if on same machine
# For now, copy from a known location - adjust path as needed
ARG OAUTH_PUBLIC_KEY_PATH=../hqauth/config/jwt/public.pem
COPY ${OAUTH_PUBLIC_KEY_PATH} ./config/jwt/public.pem

RUN composer dump-autoload --optimize --classmap-authoritative

# Skip cache warmup - do it at runtime instead (saves build time)
RUN chown -R www-data:www-data var/

# ==========================================
# Production stage
# ==========================================
FROM base AS production

RUN { \
    echo 'memory_limit = 256M'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 256'; \
    echo 'opcache.validate_timestamps = 0'; \
    echo 'session.save_handler = redis'; \
    echo 'session.save_path = "tcp://redis:6379"'; \
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

ENV APP_ENV=prod APP_DEBUG=0

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
