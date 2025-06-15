.DEFAULT_GOAL := help
init: build-containers run-containers composer import-db run-api
run: run-containers run-api 
build: ## Build docker comoppse project
	@docker compose build
up: ## Run docker compose project
	@docker compose up -d
run-api:
	@symfony server:start
import-db:
	@bin/console d:d:i flags.sql
composer:
	@docker compose exec php composer install
deploy:
	@./vendor/bin/dep deploy production
welcome:
	@echo hi
run-tests:
	@docker compose exec php vendor/bin/phpunit
psalm:
	@docker compose exec php vendor/bin/psalm --no-cache
fix:
	@docker compose exec php vendor/bin/php-cs-fixer fix
--:
	@docker compose exec php sh -c "$(filter-out $@,$(MAKECMDGOALS) $(MAKEFLAGS))"
sh: ## Shell access into php container
	@docker compose exec php sh
dumper:
	@docker compose exec php vendor/bin/var-dump-server
domain-upd: ## Set webhook url for bot, needs named argument e.g. domain-upd url=https://url.com
	@curl -X POST https://api.telegram.org/bot$(BOT_TOKEN)/setWebhook -d "url=$(url)"
domain-check: ## Check domain
	@curl -s "https://api.telegram.org/bot$(BOT_TOKEN)/getWebhookInfo"
domain-me: ## Check domain
	@curl -s "https://api.telegram.org/bot$(BOT_TOKEN)/getMe"
%:
	@

help:
	@echo "Usage: make target"
	@echo "Available targets:"
	@awk '/^[a-zA-Z0-9\-_]+:/ { \
		match($$0, /^[a-zA-Z0-9\-_]+:/, target); \
		target_name = substr(target[0], 1, length(target[0]) - 1); \
		if (match($$0, /##[[:space:]]*(.*)/, desc)) { \
			printf "\033[34m%-20s\033[0m  %s\n", target_name, desc[1]; \
		} else { \
		printf "\033[34m%-20s\033[0m  \033[30mn/a \033[0m\n", target_name; \
		} \
	}' Makefile | sort 

# BOT_TOKEN received from there
include .env.local

