# Oravil Academy Platform

Oravil Academy Platform is a full-stack web application built with:
- **Backend:** Laravel 12 (PHP 8.2+) · PostgreSQL 16
- **Frontend:** React 18 + Vite + TypeScript

---

## Quick Start — One Command

```bash
git clone https://github.com/oravil/oravil-academy-platform.git
cd oravil-academy-platform
make bootstrap
```

That's it. The bootstrap script will:
1. Verify all prerequisites
2. Install backend (Composer) dependencies
3. Install frontend (pnpm / npm) dependencies
4. Create `.env` files from their `.example` counterparts
5. Generate the Laravel application key
6. Start PostgreSQL via Docker Compose
7. Run database migrations
8. Print next-step instructions

> **Prerequisites:** PHP 8.3+, Composer, Node.js 20+, Docker & Docker Compose v2

---

## Prerequisites

| Tool | Minimum version | Install |
|------|----------------|---------|
| PHP | 8.3+ | [php.net](https://www.php.net/downloads) |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org/download/) |
| Node.js | 20+ | [nodejs.org](https://nodejs.org) |
| pnpm *(recommended)* | 9+ | `npm i -g pnpm` |
| Docker & Compose v2 | latest | [docs.docker.com](https://docs.docker.com/get-docker/) |

---

## Available Make Commands

```bash
make bootstrap   # One-command developer setup
make dev         # Start full dev stack (Docker + backend + frontend)
make test        # Run all tests (Pest + Vitest)
make lint        # Lint & statically analyse all code
make help        # List all available commands
```

### Additional targets

```bash
make backend-test      # Backend tests only  (php artisan test)
make frontend-test     # Frontend tests only (vitest run)
make backend-lint      # PHP style fix       (Pint)
make backend-analyse   # Static analysis     (PHPStan)
make frontend-lint     # JS/TS lint          (ESLint)
make docker-up         # docker compose up -d
make docker-down       # docker compose down
```

---

## Running Locally (manual)

### 1 — Database (Docker)

```bash
# .env.docker is created automatically by make bootstrap,
# or copy it manually:
cp .env.docker.example .env.docker

# Start PostgreSQL
docker compose up -d

# Optional: start PostgreSQL + pgAdmin
docker compose --profile tools up -d

# Verify services are healthy
docker compose ps
```

> **Note:** `.env.docker` is git-ignored. Never commit credentials.

### 2 — Backend

```bash
cd backend
composer install
cp .env.example .env          # if not already done
php artisan key:generate
php artisan migrate
php artisan serve             # http://localhost:8000
```

### 3 — Frontend

```bash
cd frontend
pnpm install                  # or: npm install
pnpm run dev                  # http://localhost:5173
```

---

## Quality Standards

| Command | What it does |
|---------|--------------|
| `make lint` | Auto-fix PHP style (Pint) + run ESLint |
| `make backend-analyse` | PHPStan static analysis |
| `cd backend && composer lint:check` | PHP style check (CI mode, no writes) |
| `cd frontend && pnpm format` | Prettier formatter |

---

## Running CI Locally

```bash
# Backend
cd backend
composer install --no-interaction --prefer-dist --no-progress
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=256M

# Frontend
cd frontend
pnpm install --frozen-lockfile
pnpm build
pnpm lint
pnpm test
```
