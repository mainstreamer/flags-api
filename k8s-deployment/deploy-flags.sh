#!/bin/bash
# Flags App Deployment Script

set -e

echo "========================================="
echo "Flags App Kubernetes Deployment"
echo "========================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
NAMESPACE="flags"
HQAUTH_PUBLIC_KEY_PATH="../hqauth/config/jwt/public.pem"
FLAGS_API_PATH="../flags-api"
FLAGS_FRONTEND_PATH="../flags-frontend"

# Step 1: Check prerequisites
echo -e "\n${YELLOW}Step 1: Checking prerequisites...${NC}"

if ! command -v kubectl &> /dev/null; then
    echo -e "${RED}kubectl not found. Please install kubectl first.${NC}"
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo -e "${RED}docker not found. Please install docker first.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Prerequisites OK${NC}"

# Step 2: Build and push images
echo -e "\n${YELLOW}Step 2: Building and pushing Docker images...${NC}"

read -p "Build and push images? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    
    # Build backend API
    echo -e "\n${YELLOW}Building flags-api-php...${NC}"
    cd "$FLAGS_API_PATH"
    
    # Copy public key from HQAUTH
    if [ -f "$HQAUTH_PUBLIC_KEY_PATH" ]; then
        mkdir -p config/jwt
        cp "$HQAUTH_PUBLIC_KEY_PATH" config/jwt/public.pem
        echo -e "${GREEN}✓ Copied public key from HQAUTH${NC}"
    else
        echo -e "${RED}Warning: Public key not found at $HQAUTH_PUBLIC_KEY_PATH${NC}"
        echo "Make sure to manually copy it to config/jwt/public.pem"
        read -p "Continue anyway? (y/n) " -n 1 -r
        echo
        [[ ! $REPLY =~ ^[Yy]$ ]] && exit 1
    fi
    
    docker build -f .docker/php-fpm/Dockerfile -t ghcr.io/mainstreamer/flags-api-php:latest --target production .
    docker push ghcr.io/mainstreamer/flags-api-php:latest
    echo -e "${GREEN}✓ Pushed flags-api-php${NC}"
    
    # Build backend Caddy
    echo -e "\n${YELLOW}Building flags-api-caddy...${NC}"
    docker build -f .docker/caddy/Dockerfile -t ghcr.io/mainstreamer/flags-api-caddy:latest .
    docker push ghcr.io/mainstreamer/flags-api-caddy:latest
    echo -e "${GREEN}✓ Pushed flags-api-caddy${NC}"
    
    # Build frontend
    echo -e "\n${YELLOW}Building flags-frontend...${NC}"
    cd "$FLAGS_FRONTEND_PATH"
    
    # Build React app
    npm run build
    echo -e "${GREEN}✓ Built React app${NC}"
    
    # Build and push frontend Caddy
    docker build -f Dockerfile.caddy -t ghcr.io/mainstreamer/flags-frontend-caddy:latest .
    docker push ghcr.io/mainstreamer/flags-frontend-caddy:latest
    echo -e "${GREEN}✓ Pushed flags-frontend-caddy${NC}"
fi

# Step 3: Apply Kubernetes manifests
echo -e "\n${YELLOW}Step 3: Applying Kubernetes manifests...${NC}"

read -p "Apply K8s manifests? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    
    # Apply in order
    kubectl apply -f k8s/01-namespace-storage.yaml
    echo -e "${GREEN}✓ Created namespace and storage${NC}"
    
    sleep 2
    
    kubectl apply -f k8s/02-mysql.yaml
    echo -e "${GREEN}✓ Deployed MySQL${NC}"
    
    # Wait for MySQL to be ready
    echo "Waiting for MySQL to be ready..."
    kubectl wait --for=condition=ready pod -l app=mysql -n "$NAMESPACE" --timeout=120s
    
    kubectl apply -f k8s/03-backend-api.yaml
    echo -e "${GREEN}✓ Deployed backend API${NC}"
    
    kubectl apply -f k8s/04-frontend.yaml
    echo -e "${GREEN}✓ Deployed frontend${NC}"
    
    echo -e "\n${GREEN}✓ All manifests applied${NC}"
fi

# Step 4: Display status
echo -e "\n${YELLOW}Step 4: Checking deployment status...${NC}"
kubectl get all -n "$NAMESPACE"

echo -e "\n${YELLOW}NodePort services:${NC}"
kubectl get svc -n "$NAMESPACE" -o wide

# Step 5: Instructions
echo -e "\n${GREEN}=========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}=========================================${NC}"

echo -e "\n${YELLOW}Next steps:${NC}"
echo "1. Update your secrets:"
echo "   kubectl edit secret mysql-secret -n flags"
echo "   kubectl edit secret flags-api-secret -n flags"
echo ""
echo "2. Initialize database (run ONCE):"
echo "   POD=\$(kubectl get pod -n flags -l component=php -o jsonpath='{.items[0].metadata.name}')"
echo "   kubectl exec -n flags \$POD -- bin/console doctrine:migrations:migrate --no-interaction"
echo ""
echo "3. Configure WireGuard to expose NodePorts:"
BACKEND_HTTP=$(kubectl get svc flags-api-service -n flags -o jsonpath='{.spec.ports[?(@.name=="http")].nodePort}')
BACKEND_HTTPS=$(kubectl get svc flags-api-service -n flags -o jsonpath='{.spec.ports[?(@.name=="https")].nodePort}')
FRONTEND_HTTP=$(kubectl get svc flags-frontend-service -n flags -o jsonpath='{.spec.ports[?(@.name=="http")].nodePort}')
FRONTEND_HTTPS=$(kubectl get svc flags-frontend-service -n flags -o jsonpath='{.spec.ports[?(@.name=="https")].nodePort}')

echo "   Backend API:  NodePort HTTP=$BACKEND_HTTP HTTPS=$BACKEND_HTTPS"
echo "   Frontend:     NodePort HTTP=$FRONTEND_HTTP HTTPS=$FRONTEND_HTTPS"
echo ""
echo "4. Test endpoints:"
echo "   curl http://<your-k8s-node-ip>:$BACKEND_HTTP"
echo "   curl http://<your-k8s-node-ip>:$FRONTEND_HTTP"
echo ""
echo "5. Check logs:"
echo "   kubectl logs -n flags -l app=flags-api --tail=100 -f"
echo "   kubectl logs -n flags -l app=flags-frontend --tail=100 -f"

echo -e "\n${GREEN}Done!${NC}"
