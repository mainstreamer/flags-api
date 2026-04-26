# Kubernetes manifests

Manifests for the `flags-api` namespace on k3s. The end-to-end deploy flow (CI → GHCR → cluster) is documented in [`/docs/DEPLOYMENT.md`](../docs/DEPLOYMENT.md). This README is a map of the files in this directory.

## Files

| File | Purpose |
|---|---|
| `namespace.yaml` | `flags-api` namespace |
| `configmap.yaml` | Non-secret env vars |
| `secrets.yaml` | Template — real secrets are created from `.env.prod` by `deploy.sh` |
| `mysql-deployment.yaml`, `mysql-pvc.yaml` | MySQL 9.5 + persistent volume |
| `redis-deployment.yaml`, `redis-pvc.yaml` | Redis + persistent volume |
| `php-deployment.yaml` | PHP-FPM, image `ghcr.io/mainstreamer/flags-api-php` |
| `caddy-deployment.yaml` | Caddy web server, image `ghcr.io/mainstreamer/flags-api-caddy` |
| `frontend-deployment.yaml` | Frontend template (same namespace, reaches API at `http://caddy`) |
| `services.yaml` | ClusterIP services for mysql, redis, php, caddy |
| `cors-middleware.yaml` | Traefik CORS middleware |
| `ingress.yaml` | Ingress (set the host before applying) |
| `kustomization.yaml` | Kustomize wrapper if you prefer `kubectl apply -k .` |
| `deploy.sh` | First-time bootstrap — creates namespace, secrets, then applies everything |
| `redeploy.sh` | Routine roll — `kubectl set image` on `php` and `caddy` |
| `reset-secrets.sh` | Re-create the secrets from a fresh `.env.prod` |
| `iptables-rule-add.sh` | Host-level networking helper |

## Quick reference

```bash
# Initial install (or after manifest changes)
./deploy.sh ./.env.prod

# Routine deploy after CI builds new images
./redeploy.sh              # roll to :latest
./redeploy.sh v0.1.5       # pin to a specific release

# Run migrations
kubectl exec -it deploy/php -n flags-api -- php bin/console doctrine:migrations:migrate
```

For everything else (when each script is appropriate, rollback, troubleshooting), see [`/docs/DEPLOYMENT.md`](../docs/DEPLOYMENT.md).
