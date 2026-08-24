<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;

test('s3 public url contains the AWS_URL host when set', function () {
    $cdn = 'https://files.example-cdn.test';

    config(['filesystems.disks.s3.url' => $cdn]);
    Storage::forgetDisk('s3');

    expect(Storage::disk('s3')->url('x'))->toContain('files.example-cdn.test');
});

test('s3 public url is not a CDN host when AWS_URL is empty', function () {
    config(['filesystems.disks.s3.url' => null]);
    Storage::forgetDisk('s3');

    $url = Storage::disk('s3')->url('x');
    $host = parse_url($url, PHP_URL_HOST);

    expect($host)->not->toContain('cdn');
    expect($host)->toBeIn(['minio', 'localhost', '127.0.0.1']);
});

test('asset and Vite::asset prefix ASSET_URL when set', function () {
    expect(file_get_contents(config_path('app.php')))->toContain("env('ASSET_URL')");
    expect(config()->has('app.asset_url'))->toBeTrue();

    config(['app.asset_url' => 'https://assets.example-cdn.test']);
    app()->forgetInstance('url');

    expect(asset('js/app.js'))->toContain('assets.example-cdn.test');

    $build = 'build-cdn-'.uniqid();
    $dir = public_path($build);

    File::ensureDirectoryExists($dir);
    File::put($dir.'/manifest.json', json_encode([
        'resources/js/app.js' => [
            'file' => 'assets/app-test.js',
            'src' => 'resources/js/app.js',
            'isEntry' => true,
        ],
    ]));

    try {
        Vite::useBuildDirectory($build);
        Vite::useHotFile($dir.'/hot-missing');

        expect(Vite::asset('resources/js/app.js'))->toContain('assets.example-cdn.test');
    } finally {
        File::deleteDirectory($dir);
    }
});

test('s3 url config is used for public objects only', function () {
    expect(config('filesystems.disks.s3.temporary_url'))->toBeNull();

    $filesystems = file_get_contents(config_path('filesystems.php'));
    expect($filesystems)->toContain("env('AWS_URL')");
    expect($filesystems)->not->toMatch("/'temporary_url'/");
});

test('vite config does not bake a CDN base', function () {
    $vite = file_get_contents(base_path('vite.config.js'));

    expect($vite)->not->toMatch('/\bbase\s*:/');
});
