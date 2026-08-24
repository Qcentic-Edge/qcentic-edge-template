<?php

test('broadcasting config points at reverb not pusher cloud', function () {
    expect(file_exists(config_path('broadcasting.php')))->toBeTrue();
    expect(file_exists(config_path('reverb.php')))->toBeTrue();

    $broadcasting = file_get_contents(config_path('broadcasting.php'));
    $reverb = file_get_contents(config_path('reverb.php'));

    expect($broadcasting)->toContain("'driver' => 'reverb'");
    expect($broadcasting)->toContain("env('REVERB_HOST')");
    expect($broadcasting)->not->toMatch('/pusher\.com/i');

    expect($reverb)->toContain("env('REVERB_HOST')");
    expect($reverb)->not->toMatch('/pusher\.com/i');

    expect(config('broadcasting.connections.reverb.driver'))->toBe('reverb');
    expect((string) config('broadcasting.connections.reverb.options.host'))
        ->not->toMatch('/pusher\.com/i');
});
