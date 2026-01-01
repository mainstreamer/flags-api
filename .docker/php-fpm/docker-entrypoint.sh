#!/bin/sh
set -e

# Ensure Symfony directories are writable
if [ -d /var/www/html/var ]; then
    mkdir -p /var/www/html/var/cache /var/www/html/var/log
    chmod -R 775 /var/www/html/var 2>/dev/null || true
fi

# Warmup JWKS cache (fetch public key from auth server)
if [ -f /var/www/html/bin/console ]; then
    php /var/www/html/bin/console app:jwks:warmup --no-interaction 2>/dev/null || echo "JWKS warmup skipped"
fi

# Execute CMD
exec "$@"