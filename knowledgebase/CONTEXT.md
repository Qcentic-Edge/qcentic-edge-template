# CONTEXT — shared language

Glossary for this project. Use these terms in code, notes, and conversation.
Keep entries to one or two lines. Add a term the first time it causes confusion.

## Filament 5 core

- **Panel** — top-level entry point. Registered by a `PanelProvider`; holds resources, pages, widgets, navigation.
- **Resource** — CRUD interface for one Eloquent model. Provides form, table, infolist, actions, relation managers.
- **Schema** — 5.x definition of form/infolist content (forms and infolists unified under schemas).
- **Form** — input view on a record, built from a schema.
- **Infolist** — read-only view on a record, built from a schema.
- **Table** — record list: columns, filters, actions, summaries, grouping.
- **Column** — one cell of a table.
- **Action** — anything that does something: buttons, modals, slide-overs, create/edit/delete, import/export.
- **Widget** — dashboard component (stats overview, charts).
- **Relation manager** — table for a related model, attached to a resource page.
- **Custom page** — panel page not bound to a model.
- **Cluster** — groups navigation items into a section.
- **Plugin** — package extending Filament. Free = open source, paid = locked repo behind purchase.
- **Panel plugin** — plugin that adds things to panels (widgets/resources/pages) or ships a whole panel; uses the Plugin object.
- **Standalone plugin** — plugin usable outside any panel (schema components, table columns); no Plugin object, config in its service provider.
- **Plugin object** — `Filament\Contracts\Plugin` class (`getId`/`register`/`boot`) configuring a plugin per-panel; optional but conventional for panel plugins.
- **plugin-skeleton** — filamentphp/plugin-skeleton repo, official scaffold; `php ./configure.php` stubs the package.
- **ResourceConfiguration / PageConfiguration** — 5.x classes enabling one resource/page class to be registered multiple times with different config (`Resource::make('key')`).
- **Plugin registry** — filamentphp.com/plugins. URL params that work: `?price=free`, `?q=`, `?sort=popular`, `?score=100`; version/tag filters are browser-only (URL-ignored). Plugin page = `/plugins/<slug>` (author-name + plugin-name slug).
- **Plumb / package health score** — plumbphp.dev automated Composer checks powering the registry's N/100 health score (Security/Maintenance/Ecosystem sub-scores).
- **Guard** — Laravel auth guard. Each panel can use its own via `authGuard()`; separate guards = separate sessions/user pools per panel.
- **FilamentUser contract** — `canAccessPanel(Panel $panel): bool` on the user model; gates who may enter which panel in production (locally all users pass).

## Bunny platform (deployment target)

- **Magic Containers** — bunny.net edge container runtime. Pods on bare metal, gVisor-sandboxed, **linux/amd64 only**. One app = one pod (containers share localhost); CPU autoscaling; no persistent volumes for stateless apps.
- **gVisor** — user-space kernel sandbox; syscall subset; standard apps (incl. FrankenPHP) run fine.
- **Bunny Database** — managed libSQL (SQLite fork), globally replicated. Public Preview, free, 1GB/DB cap. `libsql://[id].lite.bunnydb.net`.
- **libSQL** — open fork of SQLite with remote protocol; Bunny DB runs on bunny's own libSQL fork.
- **Hrana** — libSQL's client/server protocol; over HTTP at `/v2/pipeline` (JSON `execute`/`close` requests).
- **Embedded replica** — libSQL mode syncing a local SQLite file from a remote DB (offline writes); not our mode — we use remote-only.
- **Sticky sessions** — CDN-endpoint option pinning a client to one pod; not needed when sessions live in Bunny DB.
- **Bunny Storage** — object storage with S3-compatible API (public preview); where user uploads go in the stateless doctrine.
- **turso/libsql-laravel** — official Turso Laravel adapter (extends SQLiteConnection, FFI-based client). Requires illuminate ^11|^12 → must be forked for Laravel 13.
- **mehdiamenein/libsql-laravel** — our public fork (of Ben52's Laravel 13 fork): constraint `^13.0`, cursor()/select() signature fixes, PDO getAttribute for the database queue driver, URL-based config, CharBox empty-string patch. Installed via composer VCS repo.
- **sqld / libsql-server** — self-hosted libSQL server image `ghcr.io/tursodatabase/libsql-server`; speaks the same Hrana protocol as Bunny Database. Dev compose runs it; `command: ["/bin/sqld"]` + env `SQLD_DB_PATH` (its entrypoint wires `--db-path`/listen addr itself).

## Project decisions

- Target: Filament **5.x** only.
- Plugins: **free only**. Paid plugins get a note marked `paid — skip`, plus a free alternative when one exists.
- Deployment target: **bunny.net Magic Containers (stateless) + Bunny Database (libSQL)**; private Docker Hub registry; images built for linux/amd64.
- Database layer: **libSQL everywhere** (decided 2026-08-21): dev = local sqld container, prod = remote libSQL (Bunny DB / Turso / self-hosted sqld). Postgres dropped. Docker base = FrankenPHP **Debian** (turso/libsql native lib is glibc-only; FFI installed via install-php-extensions, `ffi.enable=1`).
- Repo role: this repo is the **living template** — products are cloned from it, template evolves in parallel. All deployment wiring stays env-parameterized.
- GitHub account for template/workflows: `mehdiamenein` (confirmed 2026-08-21; public repo `my-base-filament-template`, Apache-2.0).
