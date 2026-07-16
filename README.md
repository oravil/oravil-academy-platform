# Oravil Academy Platform

Oravil Academy Platform is bootstrapped as a full-stack foundation with:
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** React + Vite + TypeScript

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ (for React frontend)
- Docker & Docker Compose v2

## Installation

1. Clone the repository.
2. Start PostgreSQL:
   ```bash
   docker compose up -d
   ```
3. Set up backend:
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   ```
4. Set up frontend:
   ```bash
   cd frontend
   npm install
   ```

## Running Locally

- Backend:
  ```bash
  cd backend
  php artisan serve
  ```

- Frontend:
  ```bash
  cd frontend
  npm run dev
  ```

## Local Development

### Prerequisites
- Docker & Docker Compose v2
- PHP 8.3+ (for Laravel backend)
- Node.js 20+ (for React frontend)

### Database (Docker)

1. Copy the environment file:
   ```bash
   cp .env.docker.example .env.docker
   ```
2. Start PostgreSQL:
   ```bash
   docker compose up -d
   ```
3. (Optional) Start PostgreSQL + pgAdmin:
   ```bash
   docker compose --profile tools up -d
   ```
4. Verify services are healthy:
   ```bash
   docker compose ps
   ```

> **Note:** `.env.docker` is git-ignored. Never commit credentials.

## Running CI Locally

- Backend CI steps:
  ```bash
  cd backend
  composer install --no-interaction --prefer-dist --no-progress
  php artisan --version
  ```

- Frontend CI steps:
  ```bash
  cd frontend
  npm install
  npm run build
  ```
