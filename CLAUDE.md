# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Flags Quiz API - A Symfony 6.x REST API for a multiplayer flags and capitals quiz game with OAuth2/JWT authentication and Telegram login support. Fully containerized with Docker.

## Common Commands

All commands run from the `app/` directory via the makefile:

```bash
# Full initialization (build, start containers, composer install, run API)
make init

# Build and run
make build              # Docker build
make run-containers     # Start containers (docker compose up -d)
make run-api            # Start Symfony server

# Testing and code quality
make test               # Run PHPUnit tests
make psalm              # Static analysis
make fix                # Format code with php-cs-fixer

# Utilities
make sh                 # Shell into PHP container
make composer           # Run composer install in container
make dumper             # Start var-dump server
```

## Architecture

```
app/
├── src/Flags/              # Main feature module
│   ├── Controller/         # API endpoints (GameController, CapitalsController, SecurityController)
│   ├── Entity/             # Doctrine entities (User, Game, Answer, Flag, Capital, Score)
│   ├── Repository/         # Doctrine repositories
│   ├── Service/            # Business logic (CapitalsGameService, HqAuthProvider)
│   ├── Security/           # Custom authenticator (HqAuthAuthenticator)
│   ├── DTO/                # Data transfer objects
│   └── ConsoleCommand/     # CLI commands
├── config/
│   ├── packages/           # Bundle configs (doctrine, security, jwt, cors)
│   ├── jwt/pair/           # JWT public/private keys
│   └── secrets/            # Encrypted secrets per environment (dev/staging/prod)
├── migrations/             # Doctrine migrations
└── tests/Unit/             # PHPUnit tests
```

## Docker Architecture

Four containers: PHP-FPM (8.4-alpine), Caddy (2.7-alpine web server), MySQL (9.5), optional Redis.

Docker files in `.docker/` with multi-stage builds:
- `docker-compose.yml` - Base config
- `docker-compose.override.yml` - Dev overrides
- `docker-compose-prod.yml` / `docker-compose-staging.yml` - Deployment configs

Network: `backend-flags` (external)

## Authentication

- **JWT** via Lexik JWT Authentication Bundle - stateless API auth
- **OAuth2** via KnpU OAuth2 Client Bundle with custom HqAuthProvider
- **Telegram Login** - validated in SecurityController

Security firewall configured in `config/packages/secrity.yaml` (note: typo in filename).

## Key API Routes

- `/api/login` - Telegram login
- `/login`, `/oauth/check` - OAuth2 flow
- `/flags/correct/{flags}`, `/flags/scores` - Flags game
- `/capitals/game-start/{type}`, `/capitals/question/{game}`, `/capitals/answer/{game}/{country}/{answer}` - Capitals game
- `/api/incorrect`, `/api/correct` - User statistics (protected)

## Game Types (WorldRegions enum)

CAPITALS_EUROPE, CAPITALS_ASIA, CAPITALS_AFRICA, CAPITALS_AMERICAS, CAPITALS_OCEANIA

## Environment Configuration

- Secrets encrypted in `config/secrets/{env}/`
- JWT keys in `config/jwt/pair/`
- Environment files: `.env.prod`, `.env.staging`, `.env.test`

## Testing

```bash
# Run all tests
make test

# Run single test file
docker compose exec php vendor/bin/phpunit tests/Unit/Entity/GameTest.php
```

Test files in `app/tests/Unit/`. HTTP request examples in `app/http-requests/` for IDE testing.
