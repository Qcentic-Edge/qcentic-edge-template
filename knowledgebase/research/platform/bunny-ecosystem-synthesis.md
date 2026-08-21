# Bunny ecosystem synthesis — Magic Containers + Database (libSQL) for the Filament boilerplate

Kind: note (synthesis). All raw pages saved verbatim in `research/platform/bunny_*.md` (23 pages, fetched 2026-08-21 from https://bunny.net/docs/...). This note is the distilled understanding + implications for our Docker/Filament 5 template.

## 1. Magic Containers — the runtime we deploy to

Edge container platform: any Docker image, deployed as pods on bare metal across 40+ regions, sandboxed with **gVisor** (user-space kernel; standard networking/file I/O/process syscalls fine — FrankenPHP is a Go binary embedding PHP and runs on gVisor-class platforms like Cloud Run).

**Hard platform facts that shape the Dockerfile:**

- **linux/amd64 ONLY.** Images built on Apple Silicon must be cross-built: `docker build --platform linux/amd64` (buildx/QEMU, slow) or built natively in GitHub Actions amd64 runners (recommended for the template).
- **One app = one pod sandbox.** Multiple containers per app share localhost + lifecycle and scale together. Ports must not collide across containers in the same pod.
- **Endpoints** expose the app: CDN type (HTTP/S via bunny edge — TLS terminated upstream; optional sticky sessions keyed on header/cookie) or Anycast IP. Container itself serves plain HTTP on its port — our current HTTP-only port-8080 design carries over as-is.
- **Health checks**: startup / readiness / liveness, HTTP GET or TCP. Bunny strongly recommends readiness. Map to `/up` (already our HEALTHCHECK).
- **Graceful shutdown**: SIGTERM to PID 1, **30s grace** default (apps created after 2026-02-01), then SIGKILL. Rolling updates depend on fast clean shutdown. FrankenPHP as PID 1 handles SIGTERM (drains workers) — one more reason for the single-process FrankenPHP choice over nginx+php-fpm+supervisord.
- **Autoscaling**: CPU-driven, min/max replicas per region, uniform across regions. Stateless containers = safe horizontal scaling.
- **Limits (standard account)**: 8 CPU / 32 GiB per pod, 1 Gbps in/out, 500 outbound connections, **10 GB ephemeral storage — pod is evicted+restarted when consumed**, 20 apps/account, up to 10 pods/region/app. SMTP ports (25/465/587/2525) blocked.
- **CPU detection gotcha**: apps see host core count but only get 8 CPUs of quota → pin thread/worker counts explicitly (matters for any native tooling; PHP mostly unaffected).
- **App metadata env vars injected**: `BUNNYNET_MC_APPID`, `BUNNYNET_MC_PODID`, `BUNNYNET_MC_REGION`, `BUNNYNET_MC_PUBLIC_ENDPOINTS`, `BUNNYNET_MC_PODIP`, `BUNNYNET_MC_HOSTIP`, `BUNNYNET_MC_ZONE` — useful for logging/diagnostics.
- **Persistent volumes exist but are NOT for us**: regional, **one blank volume per pod** (no cross-pod sharing), 10 MB/s, no replication, no backups, node-bound (pods can't move nodes while volume attached). Correct for a single stateful sidecar, wrong for a stateless web tier. Template uses NO volumes.
- **Private registries**: Docker Hub + GHCR with username + read-only PAT. Matches the plan (private Docker Hub repo, bunny pulls from it).
- **CD pipeline**: official GitHub Action `BunnyWay/actions/container-update-image@main` (inputs: app_id, api_key, container, image_tag) after a build/push step — the template's workflow shape.

## 2. Bunny Database — the data layer (libSQL)

Managed, globally distributed **libSQL** (SQLite fork; bunny runs their own fork). **Public Preview — currently free**; limits: 50 DBs/account, **1 GB per DB** (raisable by support).

- **Connection**: `libsql://[id].lite.bunnydb.net` (SDKs) / `https://[id].lite.bunnydb.net/v2/pipeline` (raw HTTP, Hrana-over-HTTP v2 protocol: `{"requests":[{"type":"execute","stmt":{"sql":...,"args":[...]}},{"type":"close"}]}`).
- **Auth**: Bearer access tokens; **Full Access** and **Read Only** flavors; shown once; regenerating invalidates ALL tokens. CLI: `bunny db tokens create [--read-only --expiry 30d]`.
- **Magic Container injection**: linking DB→app from dashboard injects `BUNNY_DATABASE_URL` + `BUNNY_DATABASE_AUTH_TOKEN` env vars.
- **Architecture**: storage-at-rest only in Toronto or Frankfurt; regional replicas are read proxies (writes always proxied to primary); active primary chosen by latency; idle DBs spin down to object storage (cold-start latency on wake — keep warm or accept it).
- **Durability caveats**: write committed = in primary WAL; WAL uploaded every 10s/4096 frames → **up to ~10s data-loss window on failover**; **no read-your-writes on replicas**; 60s timeout during primary failover. Fine for boilerplate-scale apps; document the caveat.
- **Interactive/baton sessions** (multi-statement transactions over HTTP) exist but are gated ("contact us").
- **Metrics**: rows read/written, latency (avg/p75/p95), query count, size. Watch "rows read" — SQLite-style full scans inflate it.

## 3. Laravel 13 + Filament 5 on libSQL — the risky seam

Official Turso PHP path: `turso/libsql-laravel` v0.2.0 (2025-06, technical preview, 2.8k installs, 20 open issues):
- Extends Laravel's `SQLiteConnection` → Eloquent, migrations, query builder, transactions work via normal `DB` facade. Config: `driver=libsql`, `url` + `password` (auth token); also local-file and embedded-replica modes.
- **Requires `illuminate/database ^11|^12` — Laravel 13 NOT supported yet.** Also pins `turso/libsql dev-master`.
- Underlying `turso/libsql` v0.2.5 (php>=8.3) uses **ext-ffi** + a native libsql library, speaking Hrana remotely.

Community: `darkterminal/libsql-driver-laravel` (separately compiled native extension, ~16 installs) — worse option.

**Three stacked risks for our template (in order):**

1. **Laravel 13 constraint**: fork `turso/libsql-laravel`, widen constraint, pull via composer VCS repo (we own the boilerplate — cheap for us). Or wait upstream.
2. **FFI + musl in the image**: verified locally that the `dunglas/frankenphp:*-php8.4-alpine` PHP build has **no FFI compiled in**. Needs `install-php-extensions ffi` (compiles core ext) + `ffi.enable=1`, AND a musl-compatible build of the native libsql library (turso ships precompiled `.so`s — musl/glibc coverage unverified). **Fallback: switch base to the Debian FrankenPHP variant** (glibc, larger image ~+150MB) or a custom PHP build.
3. **Bunny-endpoint compatibility**: turso's client speaks the same Hrana protocol bunny exposes (`libsql://` URL scheme is standard); expected to work but UNTESTED against `[id].lite.bunnydb.net`. Fallback if blocked: write a thin Laravel DB driver on bunny's raw `/v2/pipeline` (but multi-statement transactions then need gated baton sessions).

SQLite-side consequences (inherited from libSQL): single-writer serialization (keep write transactions short), SQLite grammar via Laravel (migrations fine), no `ON CONFLICT DO UPDATE` grammar differences of note for boilerplate use.

## 4. Stateless container doctrine — what the boilerplate becomes

One image, zero volumes, all state in Bunny DB:

- **Sessions / cache / queue → `database` driver** on libSQL (no Redis; database cache driver gives atomic locks → scheduler `withoutOverlapping()` works across replicas).
- **Queue worker + scheduler inside the same container** (FrankenPHP serves HTTP; run `queue:work` + `schedule:work` as supervised sidecar processes — supervisord returns, or a tiny init script). Autoscaling duplicates them → guard with cache locks; make `queue:work` exit promptly on SIGTERM (30s budget).
- **Migrations at container startup** (bunny's own Laravel guide pattern: entrypoint waits for DB then `migrate --force`) — must stay backward-compatible because rolling updates run old+new replicas side by side.
- **config:cache at startup, never at build** (bunny guide is explicit; bakes build env otherwise). This changes our current "no config:cache" stance: entrypoint-time caching is the correct compromise (env is runtime, cache is per-boot, adds a few hundred ms to cold start).
- **File uploads → Bunny Storage**, never local disk: S3-compatible API now in public preview (enable per zone at creation) → Laravel `s3` disk with custom endpoint; or HTTP API with `AccessKey`. Local disk writes only to `/tmp` (ephemeral, 10GB cap).
- **Build/deploy loop**: GH Actions on amd64 runner → build `linux/amd64` → push private Docker Hub (or GHCR) → `BunnyWay/actions/container-update-image` rolling update. Local dev keeps docker-compose parity (libsql driver's "local only" file mode or a local sqld container).

## 5. Template strategy (user direction)

This repo = **living template**; product apps are cloned from it; template keeps evolving in parallel. Therefore: keep all deployment wiring (Dockerfile, compose, driver config, workflows) parameterized by env vars, app code minimal, and document the clone/parameterize procedure. Docker Hub account present on this machine is private to the owner — the public template never references it (build compose ships neutral defaults, push only on explicit user command). GitHub: public repo `mehdiamenein/my-base-filament-template` (confirmed 2026-08-21), Apache-2.0.

## Open questions to resolve at build time (next phase)

0. **Local-prod parity solved** ✅ (2026-08-21): dev compose runs `ghcr.io/tursodatabase/libsql-server` (sqld); app talks Hrana over HTTP at `http://db:8080`. Env ladder: file mode (unit tests) → local sqld (dev) → bunny (prod).
1. Fork `turso/libsql-laravel` for Laravel 13 ✅ (2026-08-21): no need to fork upstream ourselves — `Ben52/libsql-laravel` already carries the L13 fixes (connection factory signatures, cursor() TypeError, PDO getAttribute for the database queue driver, URL-based config, empty-string CharBox patch). Adopted as our fork `mehdiamenein/libsql-laravel`, installed via composer VCS repo; patch URL repointed at our fork.
2. FFI route vs Debian base ✅ (2026-08-21): Debian base chosen (`dunglas/frankenphp:1.12.7-php8.4-bookworm`, +~150MB). `install-php-extensions ffi` works; `ffi.enable=1` set in both dev and runtime stages. Alpine dropped (musl has no turso native lib).
3. Live-test turso/libsql PHP client against a real bunny DB endpoint — still open; local sqld parity validated (dev + prod bundled + prod remote-style), bunny endpoint itself untested (needs a Bunny account/DB).
4. Queue/scheduler supervision inside the FrankenPHP container (supervisord vs s6 vs shell init) + SIGTERM behavior — still open; current compose runs them as separate services, fine for docker-compose deploys.
5. Whether `SESSION_DRIVER=database` + CDN endpoint (no sticky sessions) needs any cookie tuning across regions — still open.

Validation log (2026-08-21): dev stack migrate + filament login + vite HMR + `php artisan test` green (tests hermetic via `tests/TestCase.php` config overrides — container env beats phpunit `<env>` because Laravel's env() reads `$_SERVER` first and PHPUnit force does not touch `$_SERVER`); prod bundled-sqld and prod external-sqld (Bunny simulation) both migrate + serve + healthy; queue worker processes jobs on libSQL.
