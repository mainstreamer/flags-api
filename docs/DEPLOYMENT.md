# Deployment

End-to-end guide to how `flags-api` ships from a commit to a running pod on the k8s cluster.

## Pipeline overview

```
  push to master ──► GitHub Actions ──► GHCR (php + caddy images, tagged latest/vX.Y.Z/<sha>)
                                                  │
                                                  ▼
                                 (manual) ssh to cluster, run k8s/redeploy.sh
                                                  │
                                                  ▼
                                 kubectl set image → rolling restart → live
```

CI is automatic. CD is manual — there is currently no Flux/Argo/auto-pull. A successful build does **not** restart pods on its own.

## What runs on push

Defined in `.github/workflows/pipeline.yml`.

| Trigger | What runs |
|---|---|
| PR into `master`/`main` | `check` job only (cs-fixer, psalm, phpunit) |
| Push to `master`/`main` | full pipeline: version → check → tag → build → cleanup → notify |
| Push of `v*` tag | check only (no rebuild — the tag was already built when master ran) |
| Push to any other branch | nothing |

So **only `master`/`main` produces images**. Feature branches are not built.

### Version bumping

The `version` job derives the next semver from the head commit message:

- `[major]` or `BREAKING:` / `BREAKING CHANGE:` → major bump
- `[minor]` or `feat:` / `feat(...)` → minor bump
- anything else → patch bump

The tag `vX.Y.Z` is then pushed back to the repo by `github-actions[bot]`.

### Images produced

Two images per build, pushed to GHCR:

- `ghcr.io/mainstreamer/flags-api-php` — PHP-FPM, built from `.docker/php-fpm/Dockerfile` target `production`
- `ghcr.io/mainstreamer/flags-api-caddy` — web server, built from `.docker/caddy/Dockerfile.prod`

Each is tagged with:

- `latest` (master only)
- `vX.Y.Z` (the bumped version)
- `<short-sha>` (the commit)

The `cleanup` job keeps the 6 most recent versions in GHCR (excluding `latest` and `vX.Y.Z` tags).

A Sentry release `${SENTRY_PROJECT}@X.Y.Z` is created and finalized in the same job.

A Discord webhook fires success/failure to `${{ secrets.DISCORD_WEBHOOK }}`.

## Cluster layout

Namespace: `flags-api`. Manifests live in `k8s/`.

| Workload | Image | Purpose |
|---|---|---|
| `deployment/php` | `flags-api-php:latest` | PHP-FPM on port 9000 |
| `deployment/caddy` | `flags-api-caddy:latest` | Web server on 80/443 |
| `deployment/mysql` | `mysql:9.5` | DB (PVC-backed) |
| `deployment/redis` | `redis` | cache (PVC-backed) |

Both `php` and `caddy` use `imagePullPolicy: Always` and pull from GHCR via the `ghcr-credentials` docker-registry secret.

## Deploying a new version (the day-to-day flow)

1. **Land the change on `master`** — open a PR, get green CI, merge.
2. **Wait for the pipeline** — watch the Actions tab. When build finishes, the new images are in GHCR. Discord will ping.
3. **SSH to the cluster host** (the box where `kubectl` is configured against the k3s cluster).
4. From `k8s/`, run:

   ```bash
   ./redeploy.sh              # rolls deployments to :latest
   ./redeploy.sh v0.1.5       # pin to a specific release
   ./redeploy.sh a1b2c3d      # pin to a specific commit (short sha)
   ```

   The script runs `kubectl set image` against `deployment/php` and `deployment/caddy` and waits on the rollout (120s timeout each).

5. **Run migrations if needed**:

   ```bash
   kubectl exec -it deploy/php -n flags-api -- php bin/console doctrine:migrations:migrate
   ```

### Why a manual `redeploy.sh` even with `imagePullPolicy: Always`

`Always` only matters when a pod is being created. Pushing a new image to the `:latest` tag does not restart anything. `kubectl set image` (even setting it to the same `:latest`) bumps the pod template hash, which is what triggers the rollout and the fresh pull.

## Initial install (one-off)

Use `k8s/deploy.sh` for a fresh cluster or after manifest changes. It:

1. Creates the namespace.
2. Creates `flags-api-secrets` from `./.env.prod` (or whatever path you pass as `$1`).
3. Creates `ghcr-credentials` from `GITH_KEY` inside that env file (username hardcoded to `mainstreamer`).
4. Applies configmap, PVCs, deployments (mysql, redis, php, caddy), services, CORS middleware, ingress.
5. Waits for each deployment to become ready.

```bash
cd k8s
./deploy.sh ./.env.prod
```

After the first install, never re-run this for routine deploys — use `redeploy.sh`. Re-run `deploy.sh` only when:

- Secrets / configmap values change (or rotate `kubectl create secret ...` directly).
- Manifests under `k8s/*.yaml` change (resource limits, replicas, ingress host, etc.).
- A new component is added.

## Rollback

To revert to the previous image:

```bash
kubectl rollout undo deployment/php -n flags-api
kubectl rollout undo deployment/caddy -n flags-api
```

Or pin to a known-good version:

```bash
./redeploy.sh v0.1.4
```

## Troubleshooting

- **Pods stuck `ImagePullBackOff`** — `ghcr-credentials` secret is missing or the PAT in `GITH_KEY` expired. Re-run the secret-creation block from `deploy.sh`.
- **Old code still running after `redeploy.sh latest`** — confirm the new digest landed: `kubectl describe pod -n flags-api -l app.kubernetes.io/name=php | grep Image:`. Compare to GHCR. If digests match but behavior is stale, you may have hit a build-cache issue — rebuild from CI with `no-cache` (the PHP build already uses `no-cache: true`, but Caddy uses build cache).
- **Migrations didn't run** — the deploy script does not auto-migrate. Run the `doctrine:migrations:migrate` exec shown above.
- **Build green but no Discord ping** — `notify` runs `if: always()` for master, so a missing message means `DISCORD_WEBHOOK` secret is unset.

## Files referenced

- `.github/workflows/pipeline.yml` — CI pipeline
- `k8s/deploy.sh` — initial cluster bootstrap
- `k8s/redeploy.sh` — routine image roll
- `k8s/php-deployment.yaml`, `k8s/caddy-deployment.yaml` — workload specs
- `.docker/php-fpm/Dockerfile`, `.docker/caddy/Dockerfile.prod` — image builds
