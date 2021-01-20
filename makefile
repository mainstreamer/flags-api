include .env
init: build-containers run-containers composer import-db run-api
run: run-containers run-api 
build-containers:
	docker-compose build
run-containers:
	docker-compose up -d
run-api:
	symfony server:start
import-db:
	bin/console d:d:i flags.sql
composer:
	composer install