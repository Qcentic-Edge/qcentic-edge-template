<?php

use App\Support\ReverbScaling;

test('reverb scaling is disabled when redis urls are empty', function () {
    expect(ReverbScaling::enabled(null))->toBeFalse();
    expect(ReverbScaling::enabled(''))->toBeFalse();
    expect(ReverbScaling::enabled('', ''))->toBeFalse();
    expect(ReverbScaling::enabled(null, null))->toBeFalse();
});

test('reverb scaling is enabled when REDIS_URL is set', function () {
    expect(ReverbScaling::enabled('redis://redis:6379'))->toBeTrue();
});

test('reverb scaling is enabled when REVERB_REDIS is set', function () {
    expect(ReverbScaling::enabled(null, 'redis://redis:6379'))->toBeTrue();
});
