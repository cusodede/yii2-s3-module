# Development environment commands
up:
	@echo "Starting development environment..."
	@[ ! -f .env ] && cp .env.example .env || true
	@[ ! -f ./tests/.env ] && cp ./tests/.env.example ./tests/.env || true
	docker compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 10
	@echo "Development environment is ready!"
	@echo "PostgreSQL: localhost:5432"
	@echo "MinIO Console: http://localhost:9001 (minioadmin/minioadmin)"
	@echo "MinIO API: http://localhost:9000"

down:
	@echo "Stopping development environment..."
	docker compose down

restart: down up

# Build/rebuild commands
build:
	@echo "Building Docker images..."
	@[ ! -f .env ] && cp .env.example .env || true
	@[ ! -f ./tests/.env ] && cp ./tests/.env.example ./tests/.env || true
	docker compose build --pull

# Force rebuild images (use after Dockerfile changes)
rebuild:
	@echo "Rebuilding Docker images from scratch..."
	@[ ! -f .env ] && cp .env.example .env || true
	@[ ! -f ./tests/.env ] && cp ./tests/.env.example ./tests/.env || true
	docker compose build --no-cache

# Test commands (runs in existing containers)
test: test84 test85

test84:
	@echo "Running tests with PHP 8.4..."
	@docker compose exec php-8.4 composer install --no-dev --optimize-autoloader
	@docker compose exec php-8.4 vendor/bin/codecept run -v --debug

test85:
	@echo "Running tests with PHP 8.5..."
	@docker compose exec php-8.5 composer install --no-dev --optimize-autoloader
	@docker compose exec php-8.5 vendor/bin/codecept run -v --debug

# Quick test - assumes composer dependencies are already installed
quick-test: quick-test84 quick-test85

quick-test84:
	@echo "Quick test with PHP 8.4..."
	@docker compose exec php-8.4 vendor/bin/codecept run -v --debug

quick-test85:
	@echo "Quick test with PHP 8.5..."
	@docker compose exec php-8.5 vendor/bin/codecept run -v --debug

# Coverage (requires PCOV — rebuild image if you don't have it: make rebuild)
coverage:
	@echo "Running tests with coverage on PHP 8.4..."
	@docker compose exec php-8.4 vendor/bin/codecept run --coverage --coverage-xml --coverage-html
	@echo "HTML report: tests/_output/coverage/index.html"

# Shell access to containers
shell84:
	@echo "Opening shell in PHP 8.4 container..."
	docker compose exec php-8.4 bash

shell85:
	@echo "Opening shell in PHP 8.5 container..."
	docker compose exec php-8.5 bash

# Development utilities
composer-install:
	@echo "Installing composer dependencies in both PHP versions..."
	docker compose exec php-8.4 composer install
	docker compose exec php-8.5 composer install

composer-update:
	@echo "Updating composer dependencies in both PHP versions..."
	docker compose exec php-8.4 composer update
	docker compose exec php-8.5 composer update

# Database operations
db-migrate:
	@echo "Running database migrations..."
	docker compose exec php-8.4 ./yii migrate --interactive=0

db-reset:
	@echo "Resetting databases (dev and test)..."
	docker compose exec postgres psql -U postgres -c "DROP DATABASE IF EXISTS s3_dev; CREATE DATABASE s3_dev;"
	docker compose exec postgres psql -U postgres -c "DROP DATABASE IF EXISTS s3_test; CREATE DATABASE s3_test;"

# Cleanup commands
clean:
	@echo "Cleaning up runtime files and dependencies..."
	rm -rf tests/runtime/*
	rm -rf composer.lock
	rm -rf vendor/

clean-all: clean
	@echo "Full cleanup including composer cache..."
	rm -rf tests/runtime/.composer*
	docker compose down --volumes --remove-orphans

# Status and logs
status:
	@echo "Container status:"
	docker compose ps

logs:
	docker compose logs -f

logs-postgres:
	docker compose logs -f postgres

logs-minio:
	docker compose logs -f minio

# Help
help:
	@echo "Available commands:"
	@echo "  up              - Start development environment"
	@echo "  down            - Stop development environment"
	@echo "  restart         - Restart development environment"
	@echo "  build           - Build Docker images"
	@echo "  rebuild         - Rebuild Docker images from scratch"
	@echo "  test            - Run tests on both PHP versions"
	@echo "  test84          - Run tests on PHP 8.4"
	@echo "  test85          - Run tests on PHP 8.5"
	@echo "  quick-test      - Quick test (no composer install)"
	@echo "  coverage        - Run tests with code coverage report (PHP 8.4)"
	@echo "  shell84         - Shell access to PHP 8.4 container"
	@echo "  shell85         - Shell access to PHP 8.5 container"
	@echo "  composer-install - Install composer dependencies"
	@echo "  composer-update  - Update composer dependencies"
	@echo "  db-migrate      - Run database migrations"
	@echo "  db-reset        - Reset databases"
	@echo "  clean           - Clean runtime files and vendor"
	@echo "  clean-all       - Full cleanup including Docker volumes"
	@echo "  status          - Show container status"
	@echo "  logs            - Show all container logs"
	@echo "  help            - Show this help"

.PHONY: up down restart build rebuild test test84 test85 quick-test quick-test84 quick-test85 coverage shell84 shell85 composer-install composer-update db-migrate db-reset clean clean-all status logs logs-postgres logs-minio help
