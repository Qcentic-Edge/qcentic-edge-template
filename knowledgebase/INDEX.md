# INDEX

Every knowledgebase note, one line each. Newest first.
Format: `YYYY-MM-DD | kind | file | one-line summary`
Kinds: `doc` (saved doc page), `plugin` (plugin note), `note` (synthesis), `decision` (ADR)

---
2026-08-21 | note | research/platform/bunny-ecosystem-synthesis.md | Bunny Magic Containers (gVisor, amd64-only, stateless pods) + Bunny Database (libSQL/Hrana) ecosystem understanding; Laravel-on-libSQL risks (L13 constraint, FFI/musl, endpoint compat); stateless boilerplate doctrine. RESOLVED 2026-08-21: Debian+FFI base, Ben52→our L13 fork, local sqld parity; full dev/prod validation logged in the note.
2026-08-21 | doc | research/platform/bunny_*.md | 23 raw bunny.net doc pages fetched 2026-08-21 (magic-containers: quickstart, limits, configuration, image-registries, sandbox, app-metadata, graceful-shutdown, health-checks, environment-variables, endpoints, persistent-volumes, deploy-with-github-actions, autoscaling, multi-container, guides/laravel; database: quickstart, connect/{sql-api,authorization,magic-containers}, replication, durability-and-consistency, limits, metrics)
2026-08-21 | doc | research/docs/users-overview.md | Panel access control (FilamentUser/canAccessPanel), auth features (login/registration/passwordReset/profile), authGuard()/authPasswordBroker(), guest access
2026-08-21 | doc | research/docs/panel-configuration.md | Multi-panel setup (make:filament-panel), per-panel path()/domain()/middleware/authMiddleware
2026-08-21 | doc | research/docs/getting-started.md | Panel entry point /admin; resources = CRUD (list/create/edit/view), widgets = Livewire dashboard components, custom pages = blank-canvas Livewire pages
2026-08-21 | index | docs-index.md | Full mirror of the Filament 5.x docs index (llms.txt)
