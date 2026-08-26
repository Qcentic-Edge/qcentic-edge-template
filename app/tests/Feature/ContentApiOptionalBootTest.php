<?php

use Illuminate\Foundation\Application;
use Mamenein\FilamentContentApi\FilamentContentApiPlugin;

test('the application boots without the content api plugin', function () {
    expect(file_get_contents(base_path('composer.json')))->not->toContain('filament-content-api');

    $this->artisan('about')->assertSuccessful();

    expect($this->app)->toBeInstanceOf(Application::class);
    expect(class_exists(FilamentContentApiPlugin::class))->toBeFalse();
});
