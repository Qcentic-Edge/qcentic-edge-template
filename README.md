# my-base-filament-template

Production-ready [Filament](https://filamentphp.com) 5.x / Laravel 13 template, packaged as a hardened Docker image with three Compose stacks: **dev**, **prod**, and **build**.

## What's inside

- **Filament 5 admin panel** (`/admin`) + app panel, user model with `is_admin` gate
- **FrankenPHP 1.12 / PHP 8.4** runtime, non-root (`uid 1000`), read-only root filesystem, no capabilities, opcache with `validate_timestamps=0` (immutable-code optimizations)
- **Multi-stage Docker build**: dev dependencies and node never reach the production image; frontend assets are compiled once and copied in as `public/build`
- **Postgres 17** as the database (session/cache/queue all in the database — zero extra services)
- **Three Compose files**:

| File | Purpose |
|---|---|
| `docker-compose.dev.yml` | Local development: bind-mounted source, Vite HMR on `:5173`, `artisan serve` on `:8090`, Postgres on `:5432`, hot reload for PHP + assets |
| `docker-compose.prod.yml` | Production: runs a published image (read-only, hardened), one-shot migrate, queue worker, scheduler. Optional bundled Postgres via `--profile bundled-db`, or point `DB_HOST` at a managed database (Bunny CDN, DigitalOcean, Supabase, …) |
| `docker-compose.build.yml` | Builds the production image locally (default `linux/amd64`). Nothing is pushed unless you explicitly run `build --push` with your own registry user |

## Quick start (dev)

```bash
cp .env.docker.dev.example .env.docker.dev
# fill in APP_KEY (see comment in the file) and DB_PASSWORD

docker compose -f docker-compose.dev.yml --env-file .env.docker.dev up -d
```

- App: http://localhost:8090
- Admin: http://localhost:8090/admin/login
- Vite HMR: http://localhost:5173
- Postgres: `localhost:5432`

Create an admin user:

```bash
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app \
  php artisan tinker --execute="App\Models\User::factory()->create(['name'=>'Admin','email'=>'admin@example.com','password'=>Hash::make('password123'),'is_admin'=>true]);"
```

## Production

### Build the image

```bash
docker compose -f docker-compose.build.yml build
```

Tag it however you like (`IMAGE_NAME=...`), and push to your own registry only when you choose to:

```bash
IMAGE_NAME=<registry-user>/my-base-filament-template:latest \
  docker compose -f docker-compose.build.yml build --push
```

### Run with a managed database (e.g. Bunny CDN managed Postgres)

```bash
cp .env.docker.prod.example .env.docker.prod
# set APP_KEY, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, IMAGE_NAME

docker compose -f docker-compose.prod.yml --env-file .env.docker.prod up -d
```

The `migrate` service waits for the database to accept TCP connections, runs `php artisan migrate --force` once, then the app, queue worker, and scheduler start.

TLS is terminated upstream (Bunny CDN edge, Traefik, Caddy, nginx, …) — the container serves plain HTTP on `:8080`.

### Run with a bundled Postgres instead

```bash
docker compose -f docker-compose.prod.yml --env-file .env.docker.prod --profile bundled-db up -d
```

Leave `DB_HOST` empty; compose defaults it to the bundled `db` service.

## Layout

```
app/                       Laravel + Filament application
  Dockerfile               multi-stage: app → dev → assets → runtime
docker-compose.dev.yml     dev stack
docker-compose.prod.yml    prod stack (external or bundled DB)
docker-compose.build.yml   image build (local, no push by default)
.env.docker.*.example      copy to .env.docker.* and fill in
knowledgebase/             Filament 5.x research notes (docs cache, plugin registry)
```

## Maintenance

- Filament docs research flow lives in `.agents/skills/filament-research/` — notes are cached under `knowledgebase/` with source URL + fetch date, and `https://filamentphp.com/docs/5.x/` is the source of truth.
- Run the test suite inside the dev stack:

```bash
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app php artisan test
```

## License

Apache-2.0 — see [LICENSE](LICENSE).
