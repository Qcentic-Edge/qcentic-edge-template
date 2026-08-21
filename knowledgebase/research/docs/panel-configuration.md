# Panel configuration (multi-panel)

Source: https://filamentphp.com/docs/5.x/panel-configuration.md — fetched 2026-08-21

## Introducing panels

By default, when you install the package, there is one panel that has been set up for you - and it lives on `/admin`. All the resources, custom pages, and dashboard widgets you create get registered to this panel.

However, you can create as many panels as you want, and each can have its own set of resources, pages and widgets.

For example, you could build a panel where users can log in at `/app` and access their dashboard, and admins can log in at `/admin` and manage the app. The `/app` panel and the `/admin` panel have their own resources, since each group of users has different requirements. Filament allows you to do that by providing you with the ability to create multiple panels.

### The default admin panel

When you run `filament:install`, a new file is created in `app/Providers/Filament` - `AdminPanelProvider.php`. This file contains the configuration for the `/admin` panel.

When this documentation refers to the "configuration", this is the file you need to edit. It allows you to completely customize the app.

### Creating a new panel

To create a new panel, you can use the `make:filament-panel` command, passing in the unique name of the new panel:

```bash
php artisan make:filament-panel app
```

This command will create a new panel called "app". A configuration file will be created at `app/Providers/Filament/AppPanelProvider.php`. You can access this panel at `/app`, but you can customize the path if you don't want that.

Since this configuration file is also a Laravel service provider, it needs to be registered in `bootstrap/providers.php` (Laravel 11 app structure and above) or `config/app.php` (Laravel 10 app structure and below). Filament will attempt to do this for you, but if you get an error while trying to access your panel then this process has probably failed.

## Changing the path

In a panel configuration file, you can change the path that the app is accessible at using the `path()` method:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->path('app');
}
```

If you want the app to be accessible without any prefix, you can set this to be an empty string:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->path('');
}
```

Make sure your `routes/web.php` file doesn't already define the `''` or `'/'` route, as it will take precedence.

## Setting a domain

By default, Filament will respond to requests from all domains. If you'd like to scope it to a specific domain, you can use the `domain()` method, similar to `Route::domain()` in Laravel:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->domain('admin.example.com');
}
```

## Applying middleware

You can apply extra middleware to all routes by passing an array of middleware classes to the `middleware()` method in the configuration. Middleware can be made persistent (run on every request, including Livewire AJAX requests) by passing `true` as the second argument (`isPersistent: true`).

You can apply middleware to all authenticated routes by passing an array of middleware classes to the `authMiddleware()` method.

Full page also covers: `maxContentWidth()`, `simplePageMaxContentWidth()`, `subNavigationPosition()`, `bootUsing()` lifecycle hook, `spa()` mode + `spaUrlExceptions()` + prefetching, `unsavedChangesAlerts()`, `databaseTransactions()`, `assets()`, `broadcasting(false)`, `strictAuthorization()`, error notification configuration (`registerErrorNotification()`, `hiddenErrorNotification()`, `disabledErrorNotification()`).
