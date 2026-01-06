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

test-build:
	@$(TEST_COMPOSE) build
test: ## Run PHPUnit tests (isolated test containers)
	@echo "Starting test containers..."
	@$(TEST_COMPOSE) up -d
	@echo "Setting up test database..."
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users test_user_1 -f Test -l User
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n
	@echo "Running tests..."
	@$(TEST_COMPOSE) exec php vendor/bin/phpunit
	@$(TEST_COMPOSE) down

test-up: ## Start test containers (keep running)
	@$(TEST_COMPOSE) up -d
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n

test-down: ## Stop test containers
	@$(TEST_COMPOSE) down

test-sh: ## Shell into test PHP container
	@$(TEST_COMPOSE) exec php sh

coverage: ## Generate code coverage report (HTML) - requires PCOV or Xdebug
	@echo "Checking for coverage driver..."
	@docker compose exec php php -r "if (!extension_loaded('pcov') && !extension_loaded('xdebug')) { echo 'Error: No coverage driver found. Install PCOV or Xdebug.\n'; echo 'In Docker, add to Dockerfile: RUN install-php-extensions pcov\n'; exit(1); }"
	@echo "Starting test environment..."
	@$(TEST_COMPOSE) up -d --build --wait
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n
	@echo "Generating coverage report..."
	@$(TEST_COMPOSE) exec php php -d pcov.enabled=1 vendor/bin/phpunit --coverage-html coverage/html --coverage-text
	@echo "Coverage report: coverage/html/index.html"
	@$(TEST_COMPOSE) down

coverage-text: ## Show code coverage in terminal
	@echo "Checking for coverage driver..."
	@docker compose exec php php -r "if (!extension_loaded('pcov') && !extension_loaded('xdebug')) { echo '\nError: No coverage driver found.\n'; echo 'Install PCOV: In Docker, add to Dockerfile: RUN install-php-extensions pcov\n'; exit(1); }"
	@echo "Starting test environment..."
	@$(TEST_COMPOSE) up -d --build --wait
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users -n 1 -f Test -l User
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n
	@$(TEST_COMPOSE) exec php php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text
	@$(TEST_COMPOSE) down

qa: ## Run full quality assurance pipeline CS-FIXER PSALM PHPUNIT
	@echo "=== Running Quality Assurance Pipeline ==="
	@echo ""
	@echo "1/5 Starting test environment..."
	@$(TEST_COMPOSE) up -d --build --wait
	@$(TEST_COMPOSE) exec php bin/console d:d:c -n --if-not-exists
	@$(TEST_COMPOSE) exec php bin/console d:m:m -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:users -n 1 -f Test -l User
	@$(TEST_COMPOSE) exec php bin/console app:populate:capitals --purge -n
	@$(TEST_COMPOSE) exec php bin/console app:populate:flags --purge -n
	@echo ""
	@echo "2/5 Checking code style (PHP CS Fixer)..."
	@$(TEST_COMPOSE) exec php vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo ""
	@echo "3/5 Checking PSR-12 compliance (PHPCS)..."
	@$(TEST_COMPOSE) exec php vendor/bin/phpcs src/ tests/ --standard=phpcs.xml.dist
	@echo ""
	@echo "4/5 Running Psalm static analysis..."
	@$(TEST_COMPOSE) exec php vendor/bin/psalm
	@echo ""
	@echo "5/5 Running PHPUnit tests..."
	@$(TEST_COMPOSE) exec php vendor/bin/phpunit
	@echo ""
	@echo "=== Quality Assurance Complete ==="
	@$(TEST_COMPOSE) down

pipeline: qa ## Alias for qa (run full pipeline like GitHub Actions)

psalm: ## Run Psalm static analysis
	@docker compose exec php vendor/bin/psalm --no-cache

cs-fix: ## Fix code style (PHP CS Fixer + PHPCS)
	@echo "Fixing code style with PHP CS Fixer..."
	@docker compose exec php vendor/bin/php-cs-fixer fix

cs-check: ## Check code style without fixing (PHP CS Fixer only)
	@echo "Checking code style with PHP CS Fixer..."
	@docker compose exec php vendor/bin/php-cs-fixer check

phpcs-check: ## Check line length and PSR-12 standards (PHPCS only)
	@docker compose exec php vendor/bin/phpcs src/ tests/ --standard=phpcs.xml.dist

phpcs-fix: ## Auto-fix line length and PSR-12 standards (PHPCS only)
	@docker compose exec php vendor/bin/phpcbf src/ tests/ --standard=phpcs.xml.dist

clean: ## Clean generated files
	@rm -rf coverage/
	@rm -rf .phpunit.cache/
	@rm -f .php-cs-fixer.cache
	@echo "Cleaned generated files"

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