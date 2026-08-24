<?php

use Illuminate\Support\Facades\Storage;

test('s3 disk endpoint and path-style come from env', function () {
    expect(config('filesystems.disks.s3.endpoint'))->toBe('http://minio:9000');
    expect(config('filesystems.disks.s3.use_path_style_endpoint'))->toBeTrue();
    expect(config('filesystems.disks.s3.retain_visibility'))->toBeFalse();
});

test('s3 disk can store and retrieve a file', function () {
    Storage::fake('s3');

    Storage::disk('s3')->put('hello.txt', 'world');

    expect(Storage::disk('s3')->get('hello.txt'))->toBe('world');
});
