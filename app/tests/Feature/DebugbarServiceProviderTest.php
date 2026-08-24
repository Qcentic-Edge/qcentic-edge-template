<?php

use Fruitcake\LaravelDebugbar\ServiceProvider as DebugbarServiceProvider;

test('debugbar service provider is not registered when debug is false', function () {
    expect(config('app.debug'))->toBeFalse();
    expect($this->app->providerIsLoaded(DebugbarServiceProvider::class))->toBeFalse();
});
