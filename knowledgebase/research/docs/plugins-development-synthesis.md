# Plugin development — synthesis

Sources (all fetched 2026-08-21):
- https://filamentphp.com/docs/5.x/plugins/getting-started.md → [plugins-getting-started.md](plugins-getting-started.md)
- https://filamentphp.com/docs/5.x/plugins/panel-plugins.md → [plugins-panel-plugins.md](plugins-panel-plugins.md)
- https://filamentphp.com/docs/5.x/plugins/building-a-panel-plugin.md → [plugins-building-a-panel-plugin.md](plugins-building-a-panel-plugin.md)
- https://filamentphp.com/docs/5.x/plugins/building-a-standalone-plugin.md → [plugins-building-a-standalone-plugin.md](plugins-building-a-standalone-plugin.md)
- https://filamentphp.com/docs/5.x/plugins/configurable-resources-and-pages.md → [plugins-configurable-resources-and-pages.md](plugins-configurable-resources-and-pages.md)

## Direct answer: how to make a Filament 5.x plugin

### 0. Mental model first

- A Filament plugin **is a Laravel package** (composer-installable, service provider, views/translations) + Filament-specific glue.
- Two contexts, can be combined in one package:
  - **Panel plugin** — adds stuff to panels (widgets, resources, pages) or ships a whole panel.
  - **Standalone plugin** — schema components/fields, table columns, filters usable outside any panel. No Plugin object; config lives in the service provider.
- Prerequisites to know: [Laravel package development](https://laravel.com/docs/packages), [spatie/laravel-package-tools](https://github.com/spatie/laravel-package-tools) (`PackageServiceProvider`), [Filament asset management](https://filamentphp.com/docs/5.x/advanced/assets).

### 1. Scaffold

Use the official skeleton: [filamentphp/plugin-skeleton](https://github.com/filamentphp/plugin-skeleton) → "Use this template" → clone → `php ./configure.php` (interactive stubbing). Then delete what you don't need (both tutorials delete `config`, `database`, `src/Commands`, `src/Facades`, `stubs`).

### 2. The two building blocks

**Service provider** (spatie package-tools; replaces the old deprecated `PluginServiceProvider`):

```php
class MyPluginServiceProvider extends PackageServiceProvider
{
    public static string $name = 'my-plugin';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)->hasViews()->hasTranslations();
    }

    public function packageBooted(): void
    {
        // Register assets (CSS/JS/Alpine) with FilamentAsset here.
        // Livewire components here too (panel-plugin tutorial).
    }
}
```

**Plugin object** (panel plugins only) — implements `Filament\Contracts\Plugin`:

```php
class BlogPlugin implements Plugin
{
    public function getId(): string { return 'blog'; }           // unique per project
    public function register(Panel $panel): void { /* resources/pages/widgets/themes/render hooks */ }
    public function boot(Panel $panel): void { /* runs only when panel is in use, via middleware */ }
}
```

Users register it: `$panel->plugin(BlogPlugin::make())`. Convention: `public static function make(): static => app(static::class)` (container-resolvable, test-swap-friendly). Config options = setter (fluent, stores property) + getter; read them inside `register()` which runs after user config. Access from anywhere: `filament('blog')->hasAuthorResource()` or typed `BlogPlugin::get()`.

### 3. Asset rules (the main gotcha)

- **Always** register assets in the service provider's `packageBooted()` via `FilamentAsset::register([...], package: 'vendor/name')`.
- Load-on-demand: `AlpineComponent::make(...)` + `x-load` / `x-load-src` in the view; `Css::make(...)->loadedOnRequest()` + `x-load-css`. Both tutorials rely on async loading.
- Assets needed on every page of a panel belong in the Plugin object's `register()` via `$panel->assets()` instead — otherwise they load in every panel even when the plugin isn't registered there.
- Styling: prefer users' custom filament theme over shipping CSS (panel-plugin tutorial deletes `resources/css`).

### 4. Distributing a whole panel

Extend `Filament\PanelProvider` in the package, register it under `extra.laravel.providers` in composer.json. User installs → whole panel pre-built (`/blog` etc.).

### 5. Standalone schema component (no Plugin object)

Extend `Filament\Schemas\Components\Component` (see headings tutorial): `final public function __construct`, `public static function make()` via `app(static::class, [...])`, fluent setters, `protected string $view = 'package::view'`, `evaluate()` for closures. Register views via `$package->hasViews()`.

### 6. Configurable resources/pages (5.x feature)

Register one resource class multiple times with per-registration config:
- Config class extends `ResourceConfiguration` (or `PageConfiguration` for pages), fluent setters/getters; base class already has `slug()`.
- Resource opts in via `protected static ?string $configurationClass = ...`, which enables `OrderResource::make('active')` registrations with unique keys.
- Slugs: default `{base}/{key}`; `slug()` overrides whole slug. Runtime: `static::getConfiguration()` (null = default registration), `static::hasConfiguration()`, `getUrl(configuration: 'active')`, `OrderResource::withConfiguration('archived', fn () => ...)`.
- In a plugin class: accept configuration objects from users and spread them in `register()`: `$panel->resources([TaskResource::class, ...$this->taskResourceConfigurations])`.

### 7. Ship it

README with install + usage, write tests (tutorials keep the testing dir), translations via `hasTranslations()` + `package::key` keys, submit to the registry at https://filamentphp.com/author (GitHub OAuth) — see [../plugins/find-plugins-guide.md](../plugins/find-plugins-guide.md).

## Reference plugins from the tutorials

- Panel plugin: https://github.com/awcodes/clock-widget (widget + async Alpine component)
- Standalone plugin: https://github.com/awcodes/headings (schema component + lazy CSS)
