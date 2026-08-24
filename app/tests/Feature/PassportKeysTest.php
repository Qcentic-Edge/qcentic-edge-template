<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\ClientRepository;

uses(DatabaseMigrations::class);

test('passport keys come from env pem and disk key files are not required', function () {
    expect(config('passport.private_key'))->toContain('BEGIN')->toContain('PRIVATE KEY');
    expect(config('passport.public_key'))->toContain('BEGIN')->toContain('PUBLIC KEY');
    expect(config('passport.private_key'))->toBe(env('PASSPORT_PRIVATE_KEY'));
    expect(config('passport.public_key'))->toBe(env('PASSPORT_PUBLIC_KEY'));

    expect(file_exists(storage_path('oauth-private.key')))->toBeFalse();
    expect(file_exists(storage_path('oauth-public.key')))->toBeFalse();

    app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');

    $token = User::factory()->create()->createToken('env-keys')->accessToken;

    expect($token)->toBeString()->not->toBeEmpty();
});

test('env examples document passport pem keys', function () {
    $files = [
        base_path('.env.example'),
        dirname(base_path()).'/.env.docker.dev.example',
        dirname(base_path()).'/.env.docker.prod.example',
    ];

    foreach ($files as $file) {
        $contents = file_get_contents($file);

        expect($contents)->toContain('PASSPORT_PRIVATE_KEY');
        expect($contents)->toContain('PASSPORT_PUBLIC_KEY');
    }
});
