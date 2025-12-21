# Flags App Deployment Quick Reference

## File Structure
```
flags-app/
├── k8s/
│   ├── 01-namespace-storage.yaml    # Namespace + PV/PVC
│   ├── 02-mysql.yaml                # MySQL deployment
│   ├── 03-backend-api.yaml          # PHP + Caddy backend
│   └── 04-frontend.yaml             # React frontend
├── deploy-flags.sh                  # Automated deployment
├── docker-compose.local.yaml        # Local development
├── flags-api-php.Dockerfile         # Updated with public key copy
└── flags-frontend-caddy.Dockerfile  # React static server
```

## Pre-Deployment Checklist

### 1. Copy Public Key from HQAUTH
```bash
# Manual method
cp /path/to/hqauth/config/jwt/public.pem /path/to/flags-api/config/jwt/public.pem

# Or let the build script handle it
```

### 2. Update Secrets
Edit these files before deploying:

**k8s/02-mysql.yaml:**
```yaml
stringData:
  root-password: "CHANGE_ME_ROOT_PASSWORD"
  database: "flags_db"
  username: "flags_user"
  password: "CHANGE_ME_USER_PASSWORD"
```

**k8s/03-backend-api.yaml:**
```yaml
stringData:
  app-secret: "CHANGE_ME_APP_SECRET_32_CHARS_MIN"
  database-url: "mysql://flags_user:CHANGE_ME_USER_PASSWORD@mysql:3306/flags_db?serverVersion=8.0&charset=utf8mb4"
  oauth-client-id: "your-hqauth-client-id"
  oauth-client-secret: "your-hqauth-client-secret"
```

### 3. Adjust NodePorts
Edit NodePort values in services (must be 30000-32767):
- Backend HTTP: 30080 → your choice
- Backend HTTPS: 30443 → your choice  
- Frontend HTTP: 30081 → your choice
- Frontend HTTPS: 30444 → your choice

## Deployment Commands

### Option A: Automated Script
```bash
./deploy-flags.sh
```

### Option B: Manual Step-by-Step

```bash
# 1. Create namespace and storage
kubectl apply -f k8s/01-namespace-storage.yaml

# 2. Deploy MySQL
kubectl apply -f k8s/02-mysql.yaml

# Wait for MySQL to be ready
kubectl wait --for=condition=ready pod -l app=mysql -n flags --timeout=120s

# 3. Deploy backend API
kubectl apply -f k8s/03-backend-api.yaml

# 4. Deploy frontend
kubectl apply -f k8s/04-frontend.yaml

# 5. Check status
kubectl get all -n flags
```

## Building and Pushing Images

### Backend API (PHP)
```bash
cd /path/to/flags-api

# Copy public key first
cp ../hqauth/config/jwt/public.pem config/jwt/public.pem

# Build PHP image
docker build \
  -f .docker/php-fpm/Dockerfile \
  -t ghcr.io/mainstreamer/flags-api-php:latest \
  --target production \
  .

# Build Caddy image  
docker build \
  -f .docker/caddy/Dockerfile \
  -t ghcr.io/mainstreamer/flags-api-caddy:latest \
  .

# Push to registry
docker push ghcr.io/mainstreamer/flags-api-php:latest
docker push ghcr.io/mainstreamer/flags-api-caddy:latest
```

### Frontend
```bash
cd /path/to/flags-frontend

# Build React app
npm run build

# Build Caddy image with React build
docker build \
  -f Dockerfile.caddy \
  -t ghcr.io/mainstreamer/flags-frontend-caddy:latest \
  .

# Push to registry
docker push ghcr.io/mainstreamer/flags-frontend-caddy:latest
```

## Database Initialization

```bash
# Get PHP pod name
POD=$(kubectl get pod -n flags -l component=php -o jsonpath='{.items[0].metadata.name}')

# Run migrations
kubectl exec -n flags $POD -- bin/console doctrine:migrations:migrate --no-interaction

# Verify
kubectl exec -n flags $POD -- bin/console doctrine:migrations:status

# Load fixtures (if needed)
kubectl exec -n flags $POD -- bin/console doctrine:fixtures:load --no-interaction

# Check database
kubectl exec -n flags $POD -- bin/console doctrine:query:sql "SELECT COUNT(*) FROM users"
```

## WireGuard Configuration

After deployment, expose NodePorts via WireGuard:

```bash
# Get NodePort values
kubectl get svc -n flags

# Example output:
# flags-api-service      NodePort   10.x.x.x   <none>   80:30080/TCP,443:30443/TCP
# flags-frontend-service NodePort   10.x.x.x   <none>   80:30081/TCP,443:30444/TCP
```

Configure WireGuard to forward:
- `api.flags.izeebot.top:443` → `k8s-node:30443`
- `flags.izeebot.top:443` → `k8s-node:30444`

## Debugging Commands

### Check Pod Status
```bash
kubectl get pods -n flags
kubectl describe pod <pod-name> -n flags
```

### View Logs
```bash
# Backend API
kubectl logs -n flags -l component=php --tail=100 -f
kubectl logs -n flags -l component=caddy --tail=100 -f

# Frontend
kubectl logs -n flags -l app=flags-frontend --tail=100 -f

# MySQL
kubectl logs -n flags -l app=mysql --tail=100 -f
```

### Exec Into Containers
```bash
# PHP container
POD=$(kubectl get pod -n flags -l component=php -o jsonpath='{.items[0].metadata.name}')
kubectl exec -it -n flags $POD -- sh

# Inside container:
bin/console debug:router
bin/console debug:config
env | grep DATABASE
```

### Test Connectivity
```bash
# Test MySQL from PHP pod
kubectl exec -n flags $POD -- bin/console doctrine:query:sql "SELECT 1"

# Test API endpoints
kubectl run curl --image=curlimages/curl -i --tty --rm -- sh
curl http://flags-api-service.flags.svc.cluster.local
curl http://flags-frontend-service.flags.svc.cluster.local
```

### Check Services and Endpoints
```bash
kubectl get svc -n flags
kubectl get endpoints -n flags
kubectl describe svc flags-api-service -n flags
```

## Updating Deployments

### Update Backend API
```bash
# Rebuild and push image
docker build -t ghcr.io/mainstreamer/flags-api-php:v1.0.1 .
docker push ghcr.io/mainstreamer/flags-api-php:v1.0.1

# Update deployment
kubectl set image deployment/flags-api-php -n flags \
  php-fpm=ghcr.io/mainstreamer/flags-api-php:v1.0.1

# Or force pull latest
kubectl rollout restart deployment/flags-api-php -n flags
```

### Update Frontend
```bash
# Rebuild and push
npm run build
docker build -t ghcr.io/mainstreamer/flags-frontend-caddy:v1.0.1 .
docker push ghcr.io/mainstreamer/flags-frontend-caddy:v1.0.1

# Update
kubectl set image deployment/flags-frontend -n flags \
  caddy=ghcr.io/mainstreamer/flags-frontend-caddy:v1.0.1
```

### Update Secrets
```bash
# Edit directly
kubectl edit secret flags-api-secret -n flags
kubectl edit secret mysql-secret -n flags

# Or apply from file
kubectl apply -f k8s/03-backend-api.yaml
```

## Local Development

```bash
# Start local environment (mirrors K8s)
docker-compose -f docker-compose.local.yaml up -d

# Run migrations locally
docker exec flags-api-php-local bin/console doctrine:migrations:migrate

# Access services
# Backend API: http://localhost:8080
# Frontend: http://localhost:3000
# MySQL: localhost:3306
# Redis: localhost:6379
```

## Clean Up

```bash
# Delete everything
kubectl delete namespace flags

# Or delete individually
kubectl delete -f k8s/04-frontend.yaml
kubectl delete -f k8s/03-backend-api.yaml
kubectl delete -f k8s/02-mysql.yaml
kubectl delete -f k8s/01-namespace-storage.yaml

# Clean up PV (manual)
sudo rm -rf /mnt/k8s-data/flags-mysql
```

## OAuth Integration Testing

```bash
# Test OAuth discovery from K8s
POD=$(kubectl get pod -n flags -l component=php -o jsonpath='{.items[0].metadata.name}')

kubectl exec -n flags $POD -- curl https://auth.izeebot.top/.well-known/openid-configuration

# Check OAuth routes
kubectl exec -n flags $POD -- bin/console debug:router | grep auth

# Test OAuth login flow (from outside)
curl -v http://<node-ip>:30080/auth/login
```

## Common Issues

### Issue: Pods not starting
```bash
kubectl describe pod <pod-name> -n flags
# Check: image pull errors, resource limits, secrets missing
```

### Issue: Database connection failed
```bash
# Check MySQL is ready
kubectl logs -n flags -l app=mysql

# Test from PHP pod
kubectl exec -n flags $POD -- ping mysql
kubectl exec -n flags $POD -- nc -zv mysql 3306
```

### Issue: Public key not found
```bash
# Verify key is in image
kubectl exec -n flags $POD -- ls -la config/jwt/
kubectl exec -n flags $POD -- cat config/jwt/public.pem
```

### Issue: CORS errors
```bash
# Check CORS config in ConfigMap
kubectl get configmap flags-api-config -n flags -o yaml

# Update and restart
kubectl edit configmap flags-api-config -n flags
kubectl rollout restart deployment/flags-api-caddy -n flags
```

## Monitoring

```bash
# Watch all resources
watch kubectl get all -n flags

# Resource usage
kubectl top pods -n flags
kubectl top nodes

# Events
kubectl get events -n flags --sort-by='.lastTimestamp'
```
