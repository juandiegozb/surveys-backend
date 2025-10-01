# Survey App Makefile
.PHONY: help install init up down restart build logs shell artisan migrate seed test clean dev watch hot status

# Variables
SHELL := /bin/bash

# Default target
help: ## Show this help message
	@echo 'Survey App - Available commands:'
	@echo ''
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Installation and setup
install: ## Install dependencies and setup the project
	@echo "Installing project dependencies..."
	npm install
	$(MAKE) init

init: ## Initialize the complete project with Docker and DB
	@echo "Initializing Survey App with complete setup..."
	docker-compose down --volumes --remove-orphans
	docker-compose build --no-cache
	docker-compose up -d
	@echo "Waiting for services to start..."
	sleep 15
	@echo "Setting up Laravel application..."
	$(MAKE) setup-laravel
	@echo "Project initialized successfully!"
	@echo "Access at: http://localhost:8080"

setup-laravel: ## Setup Laravel application inside container
	docker-compose exec app composer install --no-interaction --optimize-autoloader
	docker-compose exec app cp .env.example .env || true
	$(MAKE) create-storage-dirs
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	docker-compose exec app php artisan migrate --force
	docker-compose exec app php artisan db:seed --force
	docker-compose exec app php artisan storage:link
	docker-compose exec app chmod -R 755 storage bootstrap/cache

create-storage-dirs: ## Create Laravel storage directories
	@echo "Creating storage directories..."
	docker-compose exec app mkdir -p storage/app/public
	docker-compose exec app mkdir -p storage/app/private
	docker-compose exec app mkdir -p storage/framework/cache/data
	docker-compose exec app mkdir -p storage/framework/sessions
	docker-compose exec app mkdir -p storage/framework/testing
	docker-compose exec app mkdir -p storage/framework/views
	@echo "Storage directories created successfully!"

# Docker commands
up: ## Start all Docker services
	@echo "Starting Docker services..."
	docker-compose up -d

down: ## Stop all Docker services
	@echo "Stopping Docker services..."
	docker-compose down

restart: ## Restart all Docker services
	@echo "Restarting Docker services..."
	$(MAKE) down
	$(MAKE) up
	@echo "Services restarted successfully!"

build: ## Build Docker containers
	@echo "Building Docker containers..."
	docker-compose build --no-cache

# Logs and debugging
logs: ## Show Docker logs
	docker-compose logs -f

logs-app: ## Show application logs
	docker-compose logs -f app

logs-localstack: ## Show LocalStack logs
	docker-compose logs -f localstack

# Development helpers
shell: ## Access application shell
	docker-compose exec app bash

artisan: ## Run artisan commands (usage: make artisan cmd="migrate")
	docker-compose exec app php artisan $(cmd)

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

# Testing
test: ## Run PHPUnit tests with SQLite in memory (fast and reliable)
	docker-compose exec app env DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test

# Maintenance
clean: ## Clean up containers, volumes, and images
	docker-compose down --volumes --remove-orphans
	docker system prune -f
	docker volume prune -f

status: ## Show status of all services
	@echo "=== Docker Containers ==="
	docker-compose ps
	@echo ""
	@echo "=== Storage Status ==="
	docker-compose exec app php -r "echo 'Local Storage: ' . (is_writable(storage_path('app/public')) ? 'WRITABLE' : 'NOT WRITABLE') . PHP_EOL;"

# Development tools
dev: ## Start development environment
	$(MAKE) up
	npm run dev

watch: ## Start asset watching
	npm run watch

hot: ## Start hot module replacement
	npm run hot
