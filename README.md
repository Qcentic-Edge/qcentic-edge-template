# my-base-filament-template

Production-ready [Filament](https://filamentphp.com) 5.x / Laravel 13 template, packaged as a hardened Docker image with three Compose stacks: **dev**, **prod**, and **build**. Database is **libSQL** everywhere — a local `sqld` server in dev, a managed libSQL database (e.g. [Bunny Database](https://bunny.net/docs/database/quickstart), Turso) in production.

## What's inside

- **Filament 5 admin panel** (`/admin`) + app panel, Spatie roles (`super_admin` / `user`) + Filament Shield (no `is_admin` flag)
- **Laravel Passport OAuth2** — personal access tokens, authorization code, refresh, and client credentials. Password grant is off. Signing keys come from `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY` (PEM in env). Panel login stays email/password session. Mint PATs from the user menu (**API tokens**).
- **FrankenPHP 1.12 / PHP 8.4** runtime (Debian base — the libSQL client's native library is glibc-only), non-root (`uid 1000`), read-only root filesystem, no capabilities, opcache with `validate_timestamps=0` (immutable-code optimizations)
- **libSQL database layer** via [turso/libsql-laravel](https://github.com/tursodatabase/libsql-laravel), installed from the Laravel 13-compatible fork [mehdiamenein/libsql-laravel](https://github.com/mehdiamenein/libsql-laravel) (constraint + runtime fixes; FFI-based client). Session/cache/queue all use the `database` driver on libSQL — zero extra services.
- **Multi-stage Docker build**: dev dependencies and node never reach the production image; frontend assets are compiled once and copied in as `public/build`
- **Three Compose files**:

| File | Purpose |
|---|---|
| `docker-compose.dev.yml` | Local development: bind-mounted source, Vite HMR on `:5173`, `artisan serve` on `:8090`, local libSQL server (`sqld`) on `:8181`, MinIO on `:9000` (API) / `:9001` (console), hot reload for PHP + assets |
| `docker-compose.prod.yml` | Production: runs a published image (read-only, hardened), one-shot migrate, queue worker, scheduler against a remote libSQL database (Bunny Database, Turso, or your own sqld). Optional bundled sqld via `--profile bundled-db` for local full-stack testing |
| `docker-compose.build.yml` | Builds the production image locally (default `linux/amd64`). Nothing is pushed unless you explicitly run `build --push` with your own registry user |

## Quick start (dev)

```bash
cp .env.docker.dev.example .env.docker.dev
# fill in APP_KEY (see comment in the file)

docker compose -f docker-compose.dev.yml --env-file .env.docker.dev up -d
```

- App: http://localhost:8090
- Admin: http://localhost:8090/admin/login
- Vite HMR: http://localhost:5173
- libSQL (Hrana over HTTP): `http://localhost:8181`
- MinIO API: http://localhost:9000
- MinIO console: http://localhost:9001 (user/password from `.env.docker.dev`)

Create an admin user (roles must already exist — `php artisan db:seed` creates `super_admin` and `user`, and assigns `super_admin` to `test@example.com`):

```bash
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app \
  php artisan tinker --execute="App\Models\User::factory()->create(['name'=>'Admin','email'=>'admin@example.com','password'=>Hash::make('password123')])->assignRole('super_admin');"
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

### Run against a managed libSQL database (Bunny Database, Turso, ...)

```bash
cp .env.docker.prod.example .env.docker.prod
# set APP_KEY, DB_URL (libsql://[id].lite.bunnydb.net), DB_AUTH_TOKEN, IMAGE_NAME

docker compose -f docker-compose.prod.yml --env-file .env.docker.prod up -d
```

The `migrate` service waits for the database to accept connections, runs `php artisan migrate --force` once, then the app, queue worker, and scheduler start.

On bunny.net Magic Containers you don't even need `DB_URL`/`DB_AUTH_TOKEN`: link your Bunny Database to the app and bunny injects `BUNNY_DATABASE_URL` + `BUNNY_DATABASE_AUTH_TOKEN`, which the compose file picks up automatically.

TLS is terminated upstream (bunny edge, Traefik, Caddy, nginx, …) — the container serves plain HTTP on `:8080`.

### Run with a bundled local sqld instead

```bash
docker compose -f docker-compose.prod.yml --env-file .env.docker.prod --profile bundled-db up -d
```

Leave `DB_URL` empty; compose defaults it to the bundled sqld service.

## Object storage (MinIO)

Dev compose runs community MinIO as the S3-compatible disk (`FILESYSTEM_DISK=s3`). The `minio-init` sidecar creates the `filament` bucket on first boot.

- API: http://localhost:9000
- Console: http://localhost:9001 (credentials = `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` in `.env.docker.dev`)
- PHP inside Compose talks to `http://minio:9000` (`AWS_ENDPOINT`). Public `Storage::url()` uses `AWS_URL=http://localhost:9000/filament` so the browser can fetch objects.
- **Livewire temporary uploads stay on the `local` disk** in this stack (`LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local`). A single app container can share that disk, and it avoids the MinIO hostname split (PHP sees `minio:9000`, the browser sees `localhost:9000`; rewriting a presigned URL host after signing breaks SigV4).
- **Multi-replica production** (Magic Containers, more than one app replica): set `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=s3` so the upload request and the form submit can land on different pods. Point `AWS_*` at Bunny Storage / AWS / R2 — MinIO is local-dev only.

## Passport (OAuth2)

API auth is [Laravel Passport](https://laravel.com/docs/13.x/passport). Enabled grants: personal access, authorization code, refresh, client credentials. **Password grant is not enabled.** Filament panel login remains a session (`web` guard).

Signing keys must live in env (Magic Containers have no persistent disk — `storage/oauth-*.key` will vanish on recycle):

```bash
php artisan passport:keys
# paste storage/oauth-private.key into PASSPORT_PRIVATE_KEY (include BEGIN/END lines)
# paste storage/oauth-public.key into PASSPORT_PUBLIC_KEY
rm storage/oauth-private.key storage/oauth-public.key
```

Or with openssl:

```bash
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -out private.pem
openssl pkey -in private.pem -pubout -out public.pem
```

Quoted multiline PEM in `.env` / Magic Containers env is fine. After migrate, `php artisan db:seed` creates a personal-access client so the panel **API tokens** page can mint PATs. Do not call `Passport::enablePasswordGrant()`.

## Tests

Tests run hermetically (sqlite `:memory:`, array session/cache, local filesystem) and are immune to the container's env vars — see `app/tests/TestCase.php`. S3 coverage uses `Storage::fake('s3')` plus phpunit env for disk config; no live MinIO in tests or CI.

```bash
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app php artisan test
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app vendor/bin/pint --test
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app vendor/bin/phpstan analyse
```

From `app/`: `composer test` / `php artisan test` (Pest), `composer lint` (Pint `--test`), `composer analyse` (Larastan level 5).

## Authorization testing

Copy this matrix into `tests/Feature/Security/` whenever you add a Filament resource (or API resource). Shared Pest helpers live in `tests/Support/authz.php`: `actingAsRole('user'|'super_admin')`, `actingAsPassport($user, $scopes)`, `asGuest()`, `assertForbiddenTo()`, `assertCannotTouchOthers($record)`. `seedUser()` / `seedSuperAdmin()` attach Spatie roles once Shield lands; until then they still create users.

| Actor | AuthN | AuthZ module | AuthZ entity |
| --- | --- | --- | --- |
| guest | 401 / login | — | — |
| user without perm | 200 login | 403 | — |
| user owner | 200 | 200 own | 403 others private |
| user view_any | 200 | 200 list | 403 mutate others unless update_any |
| super_admin | 200 | 200 | 200 |

`actingAsPassport` uses `Passport::actingAs`. Panel login stays the session `web` guard.

## Layout

```
app/                       Laravel + Filament application
  Dockerfile               multi-stage: app → dev → assets → runtime (Debian + FFI)
docker-compose.dev.yml     dev stack (local sqld + MinIO)
docker-compose.prod.yml    prod stack (remote libSQL or bundled sqld)
docker-compose.build.yml   image build (local, no push by default)
.env.docker.*.example      copy to .env.docker.* and fill in
```

## Notes

- **libSQL driver**: `turso/libsql-laravel` is a technical preview and does not support Laravel 13 upstream yet. This template installs the maintained fork `mehdiamenein/libsql-laravel` via a composer VCS repository. The fork carries Laravel 13 fixes (connection factory signatures, cursor() TypeError, PDO attribute probes used by the database queue driver) plus a patch for a native client crash on empty strings.
- **Debian base, not alpine**: the `turso/libsql` PHP client loads a glibc native library through FFI; no musl build exists.
- **Bunny Database caveats** (managed libSQL): 1 GB per DB, up to ~10s replication window, no read-your-writes on replicas. Fine for template-scale apps.

## License

Apache-2.0 — see [LICENSE](LICENSE).
