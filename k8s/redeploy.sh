## ./redeploy.sh                # Deploy latest
## ./redeploy.sh v0.1.5         # Deploy specific version
## ./redeploy.sh a1b2c3d        # Deploy specific commit

NAMESPACE="flags-api"
PHP_IMAGE="ghcr.io/mainstreamer/flags-api-php"
CADDY_IMAGE="ghcr.io/mainstreamer/flags-api-caddy"

VERSION="${1:-latest}"

echo "=== Deploying flags-api:$VERSION ==="
echo ""

# Show current versions before change
CURRENT_PHP=$(kubectl get deployment/php -n $NAMESPACE -o jsonpath='{.spec.template.spec.containers[0].image}' 2>/dev/null)
CURRENT_CADDY=$(kubectl get deployment/caddy -n $NAMESPACE -o jsonpath='{.spec.template.spec.containers[0].image}' 2>/dev/null)

echo "Current:"
echo "  php:   $CURRENT_PHP"
echo "  caddy: $CURRENT_CADDY"
echo ""
echo "Target:"
echo "  php:   $PHP_IMAGE:$VERSION"
echo "  caddy: $CADDY_IMAGE:$VERSION"
echo ""

# Set the specific image versions
kubectl set image deployment/php \
    php=$PHP_IMAGE:$VERSION \
    -n $NAMESPACE

kubectl set image deployment/caddy \
    caddy=$CADDY_IMAGE:$VERSION \
    -n $NAMESPACE

# Wait for rollouts
echo ""
echo "Waiting for php..."
kubectl rollout status deployment/php -n $NAMESPACE --timeout=120s

echo ""
echo "Waiting for caddy..."
kubectl rollout status deployment/caddy -n $NAMESPACE --timeout=120s

echo ""
echo "=== Deploy Complete ==="
kubectl get pods -n $NAMESPACE
