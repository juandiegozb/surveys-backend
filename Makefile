SHELL := /bin/bash

# Output colors
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m # No Color

# Variables
DOCKER_COMPOSE = docker compose
APP_CONTAINER = app
QUEUE_CONTAINER = queue
MYSQL_CONTAINER = mysql

##@ Main commands
.PHONY: help
help: ## Shows this help
	@echo "Survey API - Available commands:"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf "\n\033[1mCommands:\033[0m\n"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

.PHONY: init
init: up wait vendor env key migrate seed localstack-init ## Completely initializes the application
	@echo -e "$(GREEN)Application initialized successfully$(NC)"
	@echo -e "$(BLUE)Access at: http://localhost:8080$(NC)"

##@ Docker and services
.PHONY: up
up: ## Starts all services
	@echo -e "$(BLUE)Starting services...$(NC)"
	@$(DOCKER_COMPOSE) up -d --build

.PHONY: down
down: ## Stops and removes all services
	@echo -e "$(YELLOW)Stopping services...$(NC)"
	@$(DOCKER_COMPOSE) down -v

.PHONY: restart
restart: down up ## Restarts all services

.PHONY: status
status: ## Shows container status
	@$(DOCKER_COMPOSE) ps

.PHONY: logs
logs: ## Shows logs from all services
	@$(DOCKER_COMPOSE) logs --tail=100 -f

.PHONY: logs-app
logs-app: ## Shows logs from application only
	@$(DOCKER_COMPOSE) logs --tail=100 -f $(APP_CONTAINER)

##@ Container access
.PHONY: app-bash
app-bash: ## Access the application container
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) bash

.PHONY: mysql-bash
mysql-bash: ## Access the MySQL container
	@$(DOCKER_COMPOSE) exec $(MYSQL_CONTAINER) bash

.PHONY: mysql-cli
mysql-cli: ## Access MySQL CLI
	@$(DOCKER_COMPOSE) exec $(MYSQL_CONTAINER) mysql -u survey -psurvey survey

.PHONY: redis-cli
redis-cli: ## Access Redis CLI
	@$(DOCKER_COMPOSE) exec redis redis-cli

##@ Laravel and development
.PHONY: tinker
tinker: ## Access Laravel Tinker
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan tinker

.PHONY: artisan
artisan: ## Execute artisan command (usage: make artisan CMD="route:list")
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan $(CMD)

.PHONY: composer
composer: ## Execute composer command (usage: make composer CMD="require package")
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) composer $(CMD)

##@ Database
.PHONY: migrate
migrate: ## Run migrations
	@echo -e "$(BLUE)Running migrations...$(NC)"
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan migrate

.PHONY: migrate-rollback
migrate-rollback: ## Rollback last migration
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan migrate:rollback

.PHONY: migrate-fresh
migrate-fresh: ## Reset migrations (WARNING: deletes data!)
	@echo -e "$(RED)WARNING: This will delete all data$(NC)"
	@read -p "Are you sure? (y/N): " confirm && [[ $$confirm == [yY] ]] || exit 1
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan migrate:fresh

.PHONY: seed
seed: ## Run seeders
	@echo -e "$(BLUE)Running seeders...$(NC)"
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan db:seed --class=DatabaseSeeder || true

.PHONY: fresh
fresh: migrate-fresh seed ## Reset DB with fresh data

##@ Testing
.PHONY: test
test: ## Run all tests
	@echo -e "$(BLUE)Running tests...$(NC)"
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan test

.PHONY: test-coverage
test-coverage: ## Run tests with coverage
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan test --coverage

.PHONY: test-filter
test-filter: ## Run filtered tests (usage: make test-filter FILTER="UserTest")
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan test --filter=$(FILTER)

##@ Queues and jobs
.PHONY: horizon
horizon: ## Start Horizon for queues
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan horizon

.PHONY: queue-work
queue-work: ## Run queue worker
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan queue:work

.PHONY: queue-restart
queue-restart: ## Restart queue workers
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan queue:restart

##@ Cache and optimization
.PHONY: cache-clear
cache-clear: ## Clear all caches
	@echo -e "$(YELLOW)Clearing caches...$(NC)"
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan cache:clear
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan config:clear
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan route:clear
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan view:clear

.PHONY: optimize
optimize: ## Optimize application for production
	@echo -e "$(GREEN)⚡ Optimizing application...$(NC)"
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan config:cache
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan route:cache
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan view:cache

##@ Internal commands (do not use directly)
.PHONY: wait
wait:
	@echo -e "$(YELLOW)Waiting for services to be ready...$(NC)" && sleep 8

.PHONY: vendor
vendor:
	@echo -e "$(BLUE)Installing dependencies...$(NC)"
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) composer install --no-interaction

.PHONY: env
env:
	@cp -n .env.localstack .env || true

.PHONY: key
key:
	@echo -e "$(BLUE)Generating application key...$(NC)"
	@$(DOCKER_COMPOSE) exec $(APP_CONTAINER) php artisan key:generate

.PHONY: localstack-init
localstack-init:
	@echo -e "$(BLUE)Initializing LocalStack...$(NC)"
	@$(DOCKER_COMPOSE) exec localstack awslocal s3 mb s3://$$AWS_BUCKET || true
	@$(DOCKER_COMPOSE) exec localstack awslocal sqs create-queue --queue-name $$SQS_QUEUE || true

##@ Utilities
.PHONY: ps
ps: status ## Alias for status

.PHONY: build
build: ## Build Docker images
	@$(DOCKER_COMPOSE) build

.PHONY: pull
pull: ## Download latest images
	@$(DOCKER_COMPOSE) pull

.PHONY: health
health: ## Check application health
	@echo -e "$(BLUE)Checking application health...$(NC)"
	@curl -s -o /dev/null -w "Status: %{http_code}\n" http://localhost:8080 || echo -e "$(RED)Application not accessible$(NC)"

.PHONY: backup-db
backup-db: ## Create database backup
	@echo -e "$(BLUE)Creating database backup...$(NC)"
	@mkdir -p backups
	@$(DOCKER_COMPOSE) exec $(MYSQL_CONTAINER) mysqldump -u survey -psurvey survey > backups/survey_$(shell date +%Y%m%d_%H%M%S).sql
	@echo -e "$(GREEN)Backup created in backups/$(NC)"

.PHONY: clean
clean: down ## Clean containers, volumes and images
	@echo -e "$(YELLOW)Complete cleanup...$(NC)"
	@docker system prune -f
	@docker volume prune -f

# Default command
.DEFAULT_GOAL := help
