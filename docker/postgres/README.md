# docker/postgres

This directory is reserved for PostgreSQL Docker configuration files.

## Possible future additions
- `init/` — SQL scripts executed on first container start (`/docker-entrypoint-initdb.d/`)
- `conf/` — Custom `postgresql.conf` overrides

## Current setup
PostgreSQL runs as a plain `postgres:16-alpine` container.
All configuration is handled via environment variables in `.env.docker`.
