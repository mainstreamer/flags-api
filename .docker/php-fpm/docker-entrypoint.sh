#!/bin/sh
set -e

# Ensure Symfony directories are writable
if [ -d /var/www/webapp/var ]; then
mkdir -p /var/www/webapp/var/cache /var/www/webapp/var/log
chmod -R 775 /var/www/webapp/var 2>/dev/null || true
fi

# Execute CMD
exec "$@"