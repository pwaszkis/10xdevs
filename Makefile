.PHONY: help build up down restart shell logs composer npm test phpstan cs-fix

# Default target
help:
	@echo "VibeTravels Development Commands:"
	@echo ""
	@echo "  make setup         - Initial project setup (first time)"
	@echo "  make build         - Build Docker containers"
	@echo "  make up            - Start containers"
	@echo "  make down          - Stop containers"
	@echo "  make restart       - Restart containers"
	@echo "  make shell         - Access app container shell"
	@echo "  make logs          - View container logs"
	@echo ""
	@echo "  make install       - Install dependencies (composer + npm)"
	@echo "  make composer      - Run composer install"
	@echo "  make npm           - Run npm install"
	@echo ""
	@echo "  make test          - Run PHPUnit tests"
	@echo "  make test-coverage - Run tests with coverage"
	@echo "  make phpstan       - Run PHPStan analysis"
	@echo "  make cs-fix        - Fix code style (PHP CS Fixer)"
	@echo "  make cs-check      - Check code style"
	@echo "  make quality       - Run all quality checks"
	@echo ""
	@echo "  make fresh         - Fresh database with seeders"
	@echo "  make migrate       - Run database migrations"
	@echo "  make seed          - Run database seeders"
	@echo ""
	@echo "  make queue         - Start queue worker"
	@echo "  make tinker        - Run Laravel Tinker"

# Initial setup
setup: build
	docker compose run --rm app composer install
	docker compose run --rm app cp .env.example .env
	docker compose run --rm app php artisan key:generate
	docker compose up -d
	docker compose exec app php artisan migrate
	@echo "\n✅ Setup complete! Visit http://localhost"

# Docker commands
build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

shell:
	docker compose exec app bash

logs:
	docker compose logs -f

# Installation
install: composer npm

composer:
	docker compose run --rm app composer install

npm:
	docker compose run --rm node npm install

# Testing & Quality
test:
	docker compose exec app php artisan test

test-coverage:
	docker compose exec -e XDEBUG_MODE=coverage app php artisan test --coverage

phpstan:
	docker compose exec app ./vendor/bin/phpstan analyse

cs-fix:
	docker compose exec app ./vendor/bin/pint

cs-check:
	docker compose exec app ./vendor/bin/pint --test

phpcs:
	docker compose exec app ./vendor/bin/phpcs

phpcs-fix:
	docker compose exec app ./vendor/bin/phpcbf

quality: phpstan cs-check test

# Database
fresh:
	docker compose exec app php artisan migrate:fresh --seed

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

# Other
queue:
	docker compose exec app php artisan queue:work

tinker:
	docker compose exec app php artisan tinker

clear:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear
