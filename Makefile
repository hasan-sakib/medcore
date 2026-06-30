.PHONY: help install up down restart build shell artisan migrate seed test pint analyse fresh logs

APP_SERVICE = app
COMPOSE = docker compose

help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

# ── Bootstrap ───────────────────────────────────────────────────────────────

install: ## First-time setup: build images, install deps, migrate, seed
	@cp -n .env.example .env || true
	$(COMPOSE) build --no-cache
	$(COMPOSE) run --rm $(APP_SERVICE) composer install
	$(COMPOSE) run --rm $(APP_SERVICE) php artisan key:generate
	$(COMPOSE) run --rm $(APP_SERVICE) php artisan vendor:publish --tag=permission-migrations --force
	$(COMPOSE) up -d mysql redis
	@echo "Waiting for MySQL to be ready..."
	@sleep 10
	$(COMPOSE) run --rm $(APP_SERVICE) php artisan migrate --seed
	npm install
	$(COMPOSE) up -d
	@echo ""
	@echo "✅  MedCore is running!"
	@echo "   App:        http://tenant1.medcore.local"
	@echo "   Super Admin: http://admin.medcore.local/super-admin/tenants"
	@echo "   (Add *.medcore.local → 127.0.0.1 to /etc/hosts)"

# ── Docker ──────────────────────────────────────────────────────────────────

up: ## Start all containers
	$(COMPOSE) up -d

down: ## Stop all containers
	$(COMPOSE) down

restart: ## Restart all containers
	$(COMPOSE) restart

build: ## Rebuild Docker images
	$(COMPOSE) build

logs: ## Tail logs (app + queue)
	$(COMPOSE) logs -f app queue

shell: ## Open a bash shell in the app container
	$(COMPOSE) exec $(APP_SERVICE) bash

# ── Laravel ─────────────────────────────────────────────────────────────────

artisan: ## Run artisan command: make artisan CMD="route:list"
	$(COMPOSE) exec $(APP_SERVICE) php artisan $(CMD)

migrate: ## Run database migrations
	$(COMPOSE) exec $(APP_SERVICE) php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations + seeds
	$(COMPOSE) exec $(APP_SERVICE) php artisan migrate:fresh --seed

seed: ## Run database seeders
	$(COMPOSE) exec $(APP_SERVICE) php artisan db:seed

fresh: migrate-fresh ## Alias for migrate-fresh

cache-clear: ## Clear all Laravel caches
	$(COMPOSE) exec $(APP_SERVICE) php artisan optimize:clear

# ── Frontend ────────────────────────────────────────────────────────────────

npm: ## Run npm command: make npm CMD="install some-package"
	$(COMPOSE) exec vite npm $(CMD)

npm-install: ## Install npm dependencies
	$(COMPOSE) exec vite npm install

npm-build: ## Production frontend build
	$(COMPOSE) exec vite npm run build

# ── Code Quality ─────────────────────────────────────────────────────────────

test: ## Run Pest test suite
	$(COMPOSE) exec -e APP_ENV=testing $(APP_SERVICE) vendor/bin/pest --parallel

test-coverage: ## Run Pest with coverage report
	$(COMPOSE) exec -e APP_ENV=testing $(APP_SERVICE) vendor/bin/pest --coverage --min=80

pint: ## Fix code style with Laravel Pint
	$(COMPOSE) exec $(APP_SERVICE) vendor/bin/pint

pint-check: ## Check code style without fixing
	$(COMPOSE) exec $(APP_SERVICE) vendor/bin/pint --test

analyse: ## Run PHPStan static analysis
	$(COMPOSE) exec $(APP_SERVICE) vendor/bin/phpstan analyse --no-progress

type-check: ## TypeScript type check
	$(COMPOSE) exec vite npm run type-check

ci: pint-check analyse test type-check npm-build ## Run full CI suite locally

# ── Production ──────────────────────────────────────────────────────────────

prod-up: ## Start production stack
	$(COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml up -d

prod-down: ## Stop production stack
	$(COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml down

prod-deploy: ## Build and deploy production (no downtime rolling restart)
	$(COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml build
	$(COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml up -d --remove-orphans
	$(COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force
	$(COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan optimize
