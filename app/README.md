# Flags Quiz API

Symfony 6.x REST API for a multiplayer flags & capitals quiz. JWT auth, OAuth2, Telegram login. Containerized (PHP-FPM 8.4 + Caddy + MySQL 9.5 + Redis).

The full project guide for working with the code lives in [`/CLAUDE.md`](../CLAUDE.md). This README is just the quick start.

## Local development

Prerequisites: Docker + Docker Compose, the Docker network `backend-flags`, and a populated `.env` (ask another contributor for it).

```bash
make network    # one-time: create the backend-flags network
make init       # build, start containers, create DB, run migrations
make fixtures-dev   # seed users, capitals, flags
```

Then the API is reachable through Caddy on the host port mapped in `docker-compose.yml`. HTTP request examples for IDE testing live in `app/http-requests/`.

Day-to-day:

```bash
make up        # start
make down      # stop
make sh        # shell into the PHP container
make cache     # bin/console c:c
```

## Tests & QA

```bash
make qa        # full pipeline: cs-fixer + phpcs + psalm + phpunit (in isolated test containers)
make test      # phpunit only (also isolated test containers)
make t -- SomeTest   # phpunit --filter SomeTest
make coverage  # HTML coverage at coverage/html/index.html (requires PCOV — already in the image)
```

Test containers are a separate compose project (`flags-api-test`) so they don't collide with dev.

## Environments & secrets

- `.env` — local dev, never committed.
- `.env.test` — used by the test containers and CI.
- `.env.prod`, `.env.staging` — used to render Kubernetes secrets at deploy time (see `k8s/deploy.sh`).
- Symfony encrypted secrets live under `config/secrets/{env}/`. Decryption key is provided to CI as `SYMFONY_DECRYPTION_SECRET` (base64).

```bash
APP_RUNTIME_ENV=prod php bin/console secrets:set DATABASE_URL
APP_RUNTIME_ENV=prod php bin/console secrets:list --reveal
```

## Deployment

Production runs on k3s. CI builds and pushes images to GHCR on every push to `master`; rolling out to the cluster is a manual `kubectl` step. See [`/docs/DEPLOYMENT.md`](../docs/DEPLOYMENT.md) for the full flow.

There is **no** Docker Hub / `swiftcode/flags` deploy anymore, and **no** prod docker-compose flow — those were removed in favor of k8s.

## Layout

See [`/CLAUDE.md`](../CLAUDE.md#architecture) for the source tree.
