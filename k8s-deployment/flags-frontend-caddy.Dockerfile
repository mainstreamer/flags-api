# ==========================================
# Frontend Caddy - Serves React Static Files
# ==========================================
FROM caddy:2-alpine

# Copy the built React app
COPY build/ /usr/share/caddy/

# Create Caddyfile for serving React SPA
RUN echo 'flags.izeebot.top {' > /etc/caddy/Caddyfile && \
    echo '    root * /usr/share/caddy' >> /etc/caddy/Caddyfile && \
    echo '    encode gzip' >> /etc/caddy/Caddyfile && \
    echo '    file_server' >> /etc/caddy/Caddyfile && \
    echo '    try_files {path} /index.html' >> /etc/caddy/Caddyfile && \
    echo '    header {' >> /etc/caddy/Caddyfile && \
    echo '        # Security headers' >> /etc/caddy/Caddyfile && \
    echo '        X-Content-Type-Options "nosniff"' >> /etc/caddy/Caddyfile && \
    echo '        X-Frame-Options "DENY"' >> /etc/caddy/Caddyfile && \
    echo '        Referrer-Policy "strict-origin-when-cross-origin"' >> /etc/caddy/Caddyfile && \
    echo '    }' >> /etc/caddy/Caddyfile && \
    echo '}' >> /etc/caddy/Caddyfile

EXPOSE 80
EXPOSE 443

CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
