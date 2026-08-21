# INDEX

Every knowledgebase note, one line each. Newest first.
Format: `YYYY-MM-DD | kind | file | one-line summary`
Kinds: `doc` (saved doc page), `plugin` (plugin note), `note` (synthesis), `decision` (ADR)

---
2026-08-21 | note | research/docs/plugins-development-synthesis.md | How to MAKE a Filament 5.x plugin: panel vs standalone contexts, skeleton + configure.php, PackageServiceProvider + Plugin object (getId/register/boot), asset rules (packageBooted vs $panel->assets, x-load async), distributing a whole panel, configurable resources/pages, shipping/registry submission
2026-08-21 | doc | research/docs/plugins-getting-started.md | Plugin types (panel vs standalone, combinable), prerequisites (Laravel packages, spatie package-tools, asset management), Plugin object optional, plugin-skeleton + `php ./configure.php`, PluginServiceProvider deprecated → PackageServiceProvider + static $name
2026-08-21 | doc | research/docs/plugins-panel-plugins.md | Plugin class contract (getId/register/boot), make() via container, per-panel fluent config (setter/getter pairs), filament('id') access, distributing a whole panel via PanelProvider + composer extra.laravel.providers
2026-08-21 | doc | research/docs/plugins-building-a-panel-plugin.md | Panel-plugin tutorial (awcodes/clock-widget): skeleton cleanup, Livewire::component + AlpineComponent registration in packageBooted, async x-load Alpine, widget view with x-filament blade components, translations
2026-08-21 | doc | research/docs/plugins-building-a-standalone-plugin.md | Standalone-plugin tutorial (awcodes/headings): no Plugin object, schema Component subclass (final __construct, make() via app(), fluent setters, evaluate()), Css::make()->loadedOnRequest() + x-load-css lazy stylesheet
2026-08-21 | doc | research/docs/plugins-configurable-resources-and-pages.md | Register one resource/page class multiple times: ResourceConfiguration/PageConfiguration classes, configurationClass property, make('key') registrations, {base}/{key} slugs, getConfiguration()/hasConfiguration(), withConfiguration(), plugin-class example
2026-08-21 | note | research/plugins/find-plugins-guide.md | Plugin discovery guide: registry URL filters (price/q/sort/score work; version/tag URL-ignored), what a plugin page shows, 5.x + health-score reading, submission via /author (GitHub OAuth), other channels (GitHub topic, Packagist JSON, spekulatius/awesome-filament), free+5.x search recipe
2026-08-21 | note | research/platform/bunny-ecosystem-synthesis.md | Bunny Magic Containers (gVisor, amd64-only, stateless pods) + Bunny Database (libSQL/Hrana) ecosystem understanding; Laravel-on-libSQL risks (L13 constraint, FFI/musl, endpoint compat); stateless boilerplate doctrine. RESOLVED 2026-08-21: Debian+FFI base, Ben52→our L13 fork, local sqld parity; full dev/prod validation logged in the note.
2026-08-21 | doc | research/platform/bunny_*.md | 23 raw bunny.net doc pages fetched 2026-08-21 (magic-containers: quickstart, limits, configuration, image-registries, sandbox, app-metadata, graceful-shutdown, health-checks, environment-variables, endpoints, persistent-volumes, deploy-with-github-actions, autoscaling, multi-container, guides/laravel; database: quickstart, connect/{sql-api,authorization,magic-containers}, replication, durability-and-consistency, limits, metrics)
2026-08-21 | doc | research/docs/users-overview.md | Panel access control (FilamentUser/canAccessPanel), auth features (login/registration/passwordReset/profile), authGuard()/authPasswordBroker(), guest access
2026-08-21 | doc | research/docs/panel-configuration.md | Multi-panel setup (make:filament-panel), per-panel path()/domain()/middleware/authMiddleware
2026-08-21 | doc | research/docs/getting-started.md | Panel entry point /admin; resources = CRUD (list/create/edit/view), widgets = Livewire dashboard components, custom pages = blank-canvas Livewire pages
2026-08-21 | index | docs-index.md | Full mirror of the Filament 5.x docs index (llms.txt)
