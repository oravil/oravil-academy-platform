# =============================================================================
# Oravil Academy Platform — Root Makefile
# =============================================================================
# Detect the frontend package manager (prefer pnpm, fall back to npm)
PKG_MGR := $(shell command -v pnpm 2>/dev/null && echo pnpm || echo npm)

.PHONY: bootstrap dev test lint \
        backend-install backend-key backend-migrate backend-test backend-lint backend-analyse \
        frontend-install frontend-dev frontend-test frontend-lint \
        docker-up docker-down

# ── Bootstrap ─────────────────────────────────────────────────────────────────
## bootstrap: One-command developer setup (verify deps, install, .env, DB, key)
bootstrap:
	@bash scripts/bootstrap.sh

# ── Dev servers ───────────────────────────────────────────────────────────────
## dev: Start the full development stack (Docker + backend + frontend)
dev: docker-up
	@echo "Starting backend and frontend dev servers…"
	@trap 'kill 0' INT; \
	  (cd backend  && php artisan serve) & \
	  (cd frontend && $(PKG_MGR) run dev) & \
	  wait

# ── Tests ─────────────────────────────────────────────────────────────────────
## test: Run all tests (backend Pest/PHPUnit + frontend Vitest)
test: backend-test frontend-test

backend-test:
	@echo "Running backend tests…"
	cd backend && php artisan test

frontend-test:
	@echo "Running frontend tests…"
	cd frontend && $(PKG_MGR) run test

# ── Lint ──────────────────────────────────────────────────────────────────────
## lint: Lint and statically analyse all code (backend + frontend)
lint: backend-lint backend-analyse frontend-lint

backend-lint:
	@echo "Linting backend (Pint)…"
	cd backend && ./vendor/bin/pint

backend-analyse:
	@echo "Analysing backend (PHPStan)…"
	cd backend && ./vendor/bin/phpstan analyse --memory-limit=256M

frontend-lint:
	@echo "Linting frontend (ESLint)…"
	cd frontend && $(PKG_MGR) run lint

# ── Install ───────────────────────────────────────────────────────────────────
backend-install:
	cd backend && composer install --no-interaction --prefer-dist

frontend-install:
	cd frontend && $(PKG_MGR) install

backend-key:
	cd backend && php artisan key:generate

backend-migrate:
	cd backend && php artisan migrate --force

# ── Docker helpers ────────────────────────────────────────────────────────────
## docker-up: Start PostgreSQL (and optionally pgAdmin with --profile tools)
docker-up:
	docker compose up -d

## docker-down: Stop all Docker services
docker-down:
	docker compose down

# ── Help ──────────────────────────────────────────────────────────────────────
## help: Show this help message
help:
	@echo ''
	@echo 'Usage: make <target>'
	@echo ''
	@grep -E '^## ' Makefile | sed 's/## /  /' | column -t -s ':'
	@echo ''

.DEFAULT_GOAL := help
