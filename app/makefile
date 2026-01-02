include .env

.PHONY: --

CYAN := \033[0;36m
RESET := \033[0m

# Test environment uses isolated containers
TEST_COMPOSE := docker compose -f docker-compose.test.yml -p flags-test

# Default target
help: ## Show this help message
	@printf "\\nUsage: make $(CYAN)[target]$(RESET)\\n\\n"
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(CYAN)%-22s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST) | sort -f

init: build up composer import-db ## Full dev setup
run: up ## Start containers

build: ## Build all containers
	@docker compose build

up: ## Start all containers
	@docker compose up -d

down: ## Stop all containers
	@docker compose down

rebuild: down build up ## Rebuild and restart containers

db: ## Create database and run migrations
	@docker compose exec php bin/console d:d:c --if-not-exists
	@docker compose exec php bin/console d:m:m -n

import-db: ## Import flags database
	@bin/console d:d:i flags.sql

composer: ## Install PHP dependencies
	@docker compose exec php composer install

cache: ## Clear Symfony cache
	@docker compose exec php bin/console c:c

test: ## Run PHPUnit tests (isolated test containers)
	@echo "Starting test containers..."
	@$(TEST_COMPOSE) up -d --build --wait
	@echo "Setting up test database..."
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n
	@echo "Running tests..."
	@$(TEST_COMPOSE) exec php vendor/bin/phpunit
	@$(TEST_COMPOSE) down

test-up: ## Start test containers (keep running)
	@$(TEST_COMPOSE) up -d --build --wait
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n

test-down: ## Stop test containers
	@$(TEST_COMPOSE) down

test-sh: ## Shell into test PHP container
	@$(TEST_COMPOSE) exec php sh

pipeline: ## Run full CI pipeline locally (like GitHub Actions)
	@echo "=== Running CI Pipeline ==="
	@echo ""
	@echo "1/3 Starting test environment..."
	@$(TEST_COMPOSE) up -d --build --wait
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n
	@echo ""
	@echo "2/3 Running PHPUnit tests..."
	@$(TEST_COMPOSE) exec php vendor/bin/phpunit
	@echo ""
	@echo "3/3 Running Psalm static analysis..."
	@$(TEST_COMPOSE) exec php vendor/bin/psalm
	@echo ""
	@echo "=== Pipeline Complete ==="
	@$(TEST_COMPOSE) down

psalm: ## Run Psalm static analysis
	@docker compose exec php vendor/bin/psalm --no-cache

fix: ## Fix code style (PHP CS Fixer)
	@docker compose exec php vendor/bin/php-cs-fixer fix

code-style: ## Check code style without fixing
	@docker compose exec php vendor/bin/php-cs-fixer check

sh: ## Access PHP container shell
	@docker compose exec php sh

dumper: ## Start var-dump server
	@docker compose exec php vendor/bin/var-dump-server

network: ## Create Docker network
	@docker network create backend-flags

--:
	@docker compose exec php sh -c "$(filter-out $@,$(MAKECMDGOALS) $(MAKEFLAGS))"

t: ## Quick test filter (uses test containers): make t -- CorrectFlagEndpointTest
	@$(TEST_COMPOSE) exec php vendor/bin/phpunit --filter "$(filter-out $@,$(MAKECMDGOALS) $(MAKEFLAGS))"

%:
	@