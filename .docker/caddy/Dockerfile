FROM caddy:2.7-alpine

# Install necessary packages
RUN apk add --no-cache \
    shadow \
    && rm -rf /var/cache/apk/*

# Modify existing www-data group/user to match PHP-FPM (UID/GID 1000)
RUN deluser www-data 2>/dev/null || true && \
    delgroup www-data 2>/dev/null || true && \
    addgroup -g 1000 www-data && \
    adduser -D -u 1000 -G www-data www-data

# Copy Caddyfile
COPY .docker/caddy/Caddyfile /etc/caddy/Caddyfile

COPY ../../app /var/www/html

# Create log directory
RUN mkdir -p /var/log/caddy && \
    chown -R www-data:www-data /var/log/caddy

# Expose ports
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:80 || exit 1

# Caddy runs as root by default, which is fine for binding to port 80/443
# If you want to run as www-data, you'd need to use ports >1024

# Caddy runs in foreground by default
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]