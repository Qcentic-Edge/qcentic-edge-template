<?php

test('content api is optional in composer and documented in the readme', function () {
    $composer = file_get_contents(base_path('composer.json'));
    expect($composer)->not->toContain('filament-content-api');

    $readme = file_get_contents(dirname(base_path()).'/README.md');
    expect($readme)->toContain('## Optional: Content API');
    expect($readme)->toContain('composer require mamenein/filament-content-api');
    expect($readme)->toContain('php artisan filament-content-api:install');
    expect($readme)->toContain('ContentApiPlugin::make()');
});
