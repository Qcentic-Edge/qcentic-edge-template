<?php

use Illuminate\Foundation\Application;
use QcenticEdge\FilamentContentApi\FilamentContentApiPlugin;

test('the application boots without the content api plugin', function () {
    expect(file_get_contents(base_path('composer.json')))->not->toContain('filament-content-api');

    $this->artisan('about')->assertSuccessful();

    expect($this->app)->toBeInstanceOf(Application::class);
    expect(class_exists(FilamentContentApiPlugin::class))->toBeFalse();
});

test('the readme documents optional content api enablement', function () {
    $readme = file_get_contents(dirname(base_path()).'/README.md');

    expect($readme)->toContain('## Optional: Content API');
    expect($readme)->toContain('composer require qcentic-edge/filament-content-api');
    expect($readme)->toContain('php artisan filament-content-api:install');
    expect($readme)->toContain('FilamentContentApiPlugin::make()');
});
