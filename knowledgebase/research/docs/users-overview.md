# Users — overview (auth & panel access)

Source: https://filamentphp.com/docs/5.x/users/overview.md — fetched 2026-08-21

## Introduction

By default, all `App\Models\User`s can access Filament locally. To allow them to access Filament in production, you must take a few extra steps to ensure that only the correct users have access to the app.

## Authorizing access to the panel

To set up your `App\Models\User` to access Filament in non-local environments, you must implement the `FilamentUser` contract:

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    // ...

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
    }
}
```

The `canAccessPanel()` method returns `true` or `false` depending on whether the user is allowed to access the `$panel`. In this example, we check if the user's email ends with `@yourdomain.com` and if they have verified their email address.

Since you have access to the current `$panel`, you can write conditional checks for separate panels. For example, only restricting access to the admin panel while allowing all users to access the other panels of your app:

```php
public function canAccessPanel(Panel $panel): bool
{
    if ($panel->getId() === 'admin') {
        return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
    }

    return true;
}
```

## Authentication features

You can easily enable authentication features for a panel in the configuration file:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->login()
        ->registration()
        ->passwordReset()
        ->emailVerification()
        ->emailChangeVerification()
        ->profile();
}
```

Filament also supports multi-factor authentication (see `users/multi-factor-authentication`).

### Customizing the authentication features

If you'd like to replace these pages with your own, you can pass in any Filament page class to these methods. Most people will be able to make their desired customizations by extending the base page class from the Filament codebase, overriding methods like `form()`, and then passing the new page class in to the configuration:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->profile(EditProfile::class);
}
```

Base page classes you can extend:

* `Filament\Auth\Pages\Login`
* `Filament\Auth\Pages\Register`
* `Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt`
* `Filament\Auth\Pages\PasswordReset\RequestPasswordReset`
* `Filament\Auth\Pages\PasswordReset\ResetPassword`

You can also override individual field methods (e.g. `getPasswordFormComponent()`) instead of redefining the whole `form()`.

### Customizing the authentication route slugs

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->loginRouteSlug('login')
        ->registrationRouteSlug('register')
        ->passwordResetRoutePrefix('password-reset')
        // ...
}
```

### Setting the authentication guard

To set the authentication guard that Filament uses, you can pass in the guard name to the `authGuard()` configuration method:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->authGuard('web');
}
```

### Setting the password broker

To set the password broker that Filament uses, you can pass in the broker name to the `authPasswordBroker()` configuration method:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->authPasswordBroker('users');
}
```

## Setting up user avatars

Out of the box, Filament uses ui-avatars.com to generate avatars based on a user's name. If a user model has an `avatar_url` attribute, that is used instead. Customize via the `HasAvatar` contract (`getFilamentAvatarUrl()`) or a custom avatar provider registered with `->defaultAvatarProvider()`.

## Configuring the user's name attribute

Default is the `name` attribute. Change via the `HasName` contract (`getFilamentName()`).

## Setting up guest access to a panel

By default, Filament expects to work with authenticated users only. To allow guests to access a panel:

* Remove the default `Authenticate::class` from the `authMiddleware()` array in the panel configuration.
* Remove `->login()` and any other authentication features from the panel.
* Remove the default `AccountWidget` from the `widgets()` array, because it reads the current user's data.

For guest read-access in policies, make the `User $user` param optional (`?User $user`) in `viewAny()`/`view()` and return `true`, or remove those methods.
