<?php

namespace App\Providers;

use App\Events\MediaSaved;
use App\Models\User;
use App\Policies\MediaPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use QcenticEdge\FilamentInstaller\Events\InstallerUserCreated;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.debug') && class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        // Roles + Passport personal-access client before installer creates the first user.
        $this->app->booting(function (): void {
            config([
                'installer.seeders' => [
                    \Database\Seeders\RoleSeeder::class,
                    \Database\Seeders\PassportClientSeeder::class,
                ],
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(MediaHasBeenAddedEvent::class, static function (MediaHasBeenAddedEvent $event): void {
            MediaSaved::dispatch($event->media);
        });

        // The installer's first user is the super admin. Roles come from
        // installer.seeders (RoleSeeder); findOrCreate is a safety net.
        Event::listen(InstallerUserCreated::class, static function (InstallerUserCreated $event): void {
            if (! method_exists($event->user, 'assignRole')) {
                return;
            }

            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                \Spatie\Permission\Models\Role::findOrCreate('super_admin');
            }

            $event->user->assignRole('super_admin');
        });
    }
}
