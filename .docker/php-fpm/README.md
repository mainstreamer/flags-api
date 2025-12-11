# UPDATING IMAGE IN REGISTRY

### 0. Login and registry key

### 1. Rebuild
make build-prod
    or
docker build -t ghcr.io/mainstreamer/flaux:latest --target production -f docker/php/Dockerfile .

### 2. Push
docker push ghcr.io/mainstreamer/flaux:latest