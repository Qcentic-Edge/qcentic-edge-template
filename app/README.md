# Filament application

The Laravel + Filament application that gets built into the Docker image. See the repository root `README.md` for the full stack (dev / prod / build Compose files).

## Local development

From the repository root:

```bash
cp .env.docker.dev.example .env.docker.dev   # fill in APP_KEY and DB_PASSWORD
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev up -d
```

The dev container bind-mounts this directory, so PHP edits apply instantly. Frontend changes go through the Vite dev server (hot reload on `http://localhost:5173`).

## Tests

```bash
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app php artisan test
```
