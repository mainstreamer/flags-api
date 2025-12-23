include .env
default: welcome
init: build-containers run-containers composer import-db run-api
run: run-containers run-api 
db:
	@docker compose exec php bin/console d:d:c --if-not-exists
	@docker compose exec php bin/console d:m:m -n
build:
	@docker compose build
up:
	@docker compose up -d
down:
	@docker compose down
rebuild: down build up
import-db:
	@bin/console d:d:i flags.sql
composer:
	@docker compose exec php composer install
psalm:
	@docker compose exec php vendor/bin/psalm --no-cache
cache:
	@docker compose exec php bin/console c:c
welcome:
	@echo hi
test:
	@docker compose exec php vendor/bin/phpunit
psalm:
	@docker compose exec php vendor/bin/psalm --no-cache
fix:
	@docker compose exec php vendor/bin/php-cs-fixer fix
--:
	@docker compose exec php sh -c "$(filter-out $@,$(MAKECMDGOALS) $(MAKEFLAGS))"
sh:
	@docker compose exec php sh
dumper:
	@docker compose exec php vendor/bin/var-dump-server
network:
	@docker network create backend-flags
%:
	@