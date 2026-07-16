# Oravil Academy Platform

Oravil Academy Platform is bootstrapped as a full-stack foundation with:
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** React + Vite + TypeScript

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- Docker

## Installation

1. Clone the repository.
2. Start PostgreSQL:
   ```bash
   docker-compose up -d postgres
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
