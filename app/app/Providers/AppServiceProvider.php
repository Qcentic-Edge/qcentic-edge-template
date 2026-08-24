<?php

namespace App\Providers;

use App\Events\MediaSaved;
use App\Models\User;
use App\Policies\MediaPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.debug')) {
            $this->app->register(\Fruitcake\LaravelDebugbar\ServiceProvider::class);
        }
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
    }
}
