include .env
init: build-containers run-containers composer import-db run-api
run: run-containers run-api 
build:
	@docker compose build
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
psalm:
	@./vendor/bin/psalm
sh:
	@docker compose exec php sh
@:

