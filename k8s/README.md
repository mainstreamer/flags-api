Done! I've created a complete Kubernetes deployment setup in k8s/. Here's the structure:

k8s/
├── namespace.yaml          # flags-api namespace
├── configmap.yaml          # Environment variables
├── secrets.yaml            # Secrets template (edit with your values)
├── mysql-pvc.yaml          # Persistent storage for MySQL
├── mysql-deployment.yaml   # MySQL 9.0 database
├── php-deployment.yaml     # PHP-FPM (ghcr.io/mainstreamer/flags-api-php:latest)
├── caddy-deployment.yaml   # Caddy webserver (ghcr.io/mainstreamer/flags-api-caddy:latest)
├── services.yaml           # ClusterIP services for mysql, php, caddy
├── ingress.yaml            # Ingress for API (configure your domain)
├── frontend-deployment.yaml # Template for flagsapp:latest (same namespace)
├── kustomization.yaml      # Kustomize for easy deployment
└── deploy.sh               # Deployment helper script

To deploy:

1. Create GHCR credentials secret:
   kubectl create secret docker-registry ghcr-credentials \
   --docker-server=ghcr.io \
   --docker-username=YOUR_GITHUB_USERNAME \
   --docker-password=YOUR_GITHUB_PAT \
   -n flags-api

2. Edit secrets.yaml with your actual values (passwords, APP_SECRET, BOT_TOKEN, etc.)
3. Deploy with kustomize:
   cd k8s
   kubectl apply -k .

Or use the helper script:
./deploy.sh

4. Access the API via port-forward:
   kubectl port-forward svc/caddy 8080:80 -n flags-api

Frontend compatibility: The frontend-deployment.yaml template uses the same flags-api namespace and labels for easy service discovery. The frontend can reach the API at http://caddy internally.
