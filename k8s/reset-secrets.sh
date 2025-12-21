#!/bin/bash

ENV_FILE="${1:-./.env.prod}"
NAMESPACE="flags-api"

# Reset env secrets
kubectl delete secret flags-api-secrets -n $NAMESPACE --ignore-not-found
kubectl create secret generic flags-api-secrets --from-env-file="$ENV_FILE" -n $NAMESPACE

# Reset ghcr credentials
GITH_KEY=$(grep -E "^GITH_KEY=" "$ENV_FILE" | cut -d '=' -f2-)
if [ -n "$GITH_KEY" ]; then
    echo "Recreating ghcr.io registry credentials..."
    kubectl delete secret ghcr-credentials -n $NAMESPACE --ignore-not-found
    kubectl create secret docker-registry ghcr-credentials \
        --docker-server=ghcr.io \
        --docker-username=mainstreamer \
        --docker-password="$GITH_KEY" \
        -n $NAMESPACE
fi

# Restart deployments to pick up new secrets
kubectl rollout restart deployment/php -n $NAMESPACE
kubectl rollout restart deployment/caddy -n $NAMESPACE

echo "Secrets reset - done"
