#!/bin/bash
NAMESPACE="flags-api"

echo "=== Redeploying flags-api ==="

# Restart deployments to pull latest images
echo "Restarting deployments..."
kubectl rollout restart deployment/php deployment/caddy -n $NAMESPACE

# Wait for rollouts to complete
echo ""
echo "Waiting for php..."
kubectl rollout status deployment/php -n $NAMESPACE --timeout=120s

echo ""
echo "Waiting for caddy..."
kubectl rollout status deployment/caddy -n $NAMESPACE --timeout=120s

echo ""
echo "=== Redeploy Complete ==="
kubectl get pods -n $NAMESPACE
