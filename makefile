include .env
init: build-containers run-containers composer import-db run-api
run: run-containers run-api 

run-containers:
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

build:
	docker compose build
push:
	docker compose push

%:
	@
