<?php

test('reverb scaling config is off when redis urls are empty', function () {
    expect(config('reverb.servers.reverb.scaling.enabled'))->toBeFalse();
});

test('reverb scaling enabled defaults from REDIS_URL or REVERB_REDIS', function () {
    $source = file_get_contents(config_path('reverb.php'));

    expect($source)->toMatch("/'enabled'\s*=>\s*env\(\s*'REVERB_SCALING_ENABLED'/");
    expect($source)->toContain('ReverbScaling::enabled');
    expect($source)->toContain("env('REDIS_URL')");
    expect($source)->toContain("env('REVERB_REDIS')");
});

test('reverb scaling redis url prefers REVERB_REDIS then REDIS_URL', function () {
    $source = file_get_contents(config_path('reverb.php'));

    expect($source)->toContain("env('REVERB_REDIS', env('REDIS_URL'))");
});

test('dev compose redis service is behind the redis profile', function () {
    $yml = file_get_contents(dirname(base_path()).'/docker-compose.dev.yml');

    expect($yml)->toMatch('/^  redis:\n(?:.*\n)*?    profiles:\n      - redis/m');
});

test('prod compose redis service is behind the redis profile', function () {
    $yml = file_get_contents(dirname(base_path()).'/docker-compose.prod.yml');

    expect($yml)->toMatch('/^  redis:\n(?:.*\n)*?    profiles:\n      - redis/m');
});
