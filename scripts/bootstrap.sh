#!/usr/bin/env bash
# =============================================================================
# Oravil Academy Platform — Developer Bootstrap
# Usage:  bash scripts/bootstrap.sh
#         (or via: make bootstrap)
# =============================================================================
set -euo pipefail

# ── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

info()    { echo -e "${CYAN}[INFO]${RESET}  $*"; }
success() { echo -e "${GREEN}[OK]${RESET}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${RESET}  $*"; }
error()   { echo -e "${RED}[ERROR]${RESET} $*" >&2; exit 1; }

# ── Helpers ───────────────────────────────────────────────────────────────────
command_exists() { command -v "$1" &>/dev/null; }

check_version() {
  local cmd="$1" required="$2" actual
  actual=$("$cmd" --version 2>&1 | grep -oE '[0-9]+\.[0-9]+' | head -1)
  if [[ "$(printf '%s\n' "$required" "$actual" | sort -V | head -1)" != "$required" ]]; then
    error "$cmd $actual is too old — need >= $required"
  fi
}

# ── Banner ────────────────────────────────────────────────────────────────────
echo -e ""
echo -e "${BOLD}╔══════════════════════════════════════════════╗${RESET}"
echo -e "${BOLD}║   Oravil Academy Platform — Bootstrap        ║${RESET}"
echo -e "${BOLD}╚══════════════════════════════════════════════╝${RESET}"
echo -e ""

# ── 1. Prerequisites ─────────────────────────────────────────────────────────
info "Checking prerequisites…"

command_exists php    || error "PHP is not installed. Install PHP 8.3+ and try again."
check_version php 8.2
success "PHP $(php --version | head -1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"

command_exists composer || error "Composer is not installed. See https://getcomposer.org/download/"
success "Composer $(composer --version --no-ansi | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)"

command_exists node || error "Node.js is not installed. Install Node.js 20+ and try again."
check_version node 20
success "Node $(node --version)"

# Detect package manager (prefer pnpm, fall back to npm)
if command_exists pnpm; then
  PKG_MGR="pnpm"
elif command_exists npm; then
  PKG_MGR="npm"
else
  error "Neither pnpm nor npm found. Install Node.js 20+ and try again."
fi
success "Package manager: $PKG_MGR"

command_exists docker || warn "Docker not found — PostgreSQL will NOT be started automatically."
DOCKER_AVAILABLE=false
if command_exists docker; then
  if docker info &>/dev/null; then
    DOCKER_AVAILABLE=true
    success "Docker is running"
  else
    warn "Docker daemon is not running — PostgreSQL will NOT be started automatically."
  fi
fi

# ── 2. Root .env files ────────────────────────────────────────────────────────
info "Setting up root .env files…"

if [[ ! -f .env.docker ]]; then
  cp .env.docker.example .env.docker
  success "Created .env.docker from .env.docker.example"
else
  info ".env.docker already exists — skipping"
fi

# ── 3. Backend setup ──────────────────────────────────────────────────────────
info "Setting up backend…"

if [[ ! -f backend/.env ]]; then
  cp backend/.env.example backend/.env
  success "Created backend/.env from backend/.env.example"
else
  info "backend/.env already exists — skipping"
fi

info "Installing Composer dependencies…"
(cd backend && composer install --no-interaction --prefer-dist --no-progress)
success "Composer dependencies installed"

info "Generating Laravel application key…"
# Only generate if APP_KEY is empty
if grep -q '^APP_KEY=$' backend/.env 2>/dev/null; then
  (cd backend && php artisan key:generate --ansi)
  success "Laravel application key generated"
else
  info "APP_KEY already set — skipping"
fi

# ── 4. Frontend setup ─────────────────────────────────────────────────────────
info "Installing frontend dependencies (${PKG_MGR})…"
if [[ "$PKG_MGR" == "pnpm" ]]; then
  (cd frontend && pnpm install)
else
  (cd frontend && npm install)
fi
success "Frontend dependencies installed"

# ── 5. Start PostgreSQL ───────────────────────────────────────────────────────
if [[ "$DOCKER_AVAILABLE" == "true" ]]; then
  info "Starting PostgreSQL via Docker Compose…"
  docker compose up -d postgres
  success "PostgreSQL container started"

  info "Waiting for PostgreSQL to be ready…"
  RETRIES=15
  until docker compose exec -T postgres pg_isready -q 2>/dev/null || [[ $RETRIES -eq 0 ]]; do
    sleep 1
    ((RETRIES--))
  done
  if [[ $RETRIES -gt 0 ]]; then
    success "PostgreSQL is ready"
  else
    warn "PostgreSQL did not become ready in time — run migrations manually."
  fi
else
  warn "Skipping PostgreSQL start (Docker unavailable)."
  warn "Ensure a PostgreSQL 16 instance is reachable at the values in backend/.env before running migrations."
fi

# ── 6. Run migrations ─────────────────────────────────────────────────────────
if [[ "$DOCKER_AVAILABLE" == "true" ]] && [[ $RETRIES -gt 0 ]]; then
  info "Running database migrations…"
  (cd backend && php artisan migrate --force --ansi) && success "Migrations complete"
fi

# ── 7. Next steps ─────────────────────────────────────────────────────────────
echo -e ""
echo -e "${GREEN}${BOLD}Bootstrap complete!${RESET}"
echo -e ""
echo -e "${BOLD}Next steps:${RESET}"
echo -e "  ${CYAN}make dev${RESET}               — start the full dev stack (backend + frontend)"
echo -e "  ${CYAN}make test${RESET}              — run all tests (backend + frontend)"
echo -e "  ${CYAN}make lint${RESET}              — lint all code"
echo -e ""
echo -e "  Manual dev servers:"
echo -e "    Backend  → ${CYAN}cd backend  && php artisan serve${RESET}  (http://localhost:8000)"
echo -e "    Frontend → ${CYAN}cd frontend && ${PKG_MGR} run dev${RESET}    (http://localhost:5173)"
echo -e ""
echo -e "  Optional pgAdmin UI:"
echo -e "    ${CYAN}docker compose --profile tools up -d${RESET}  (http://localhost:5050)"
echo -e ""
