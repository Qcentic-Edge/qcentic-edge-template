<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;

uses(DatabaseMigrations::class);

test('guest without bearer token cannot get api user', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

test('passport personal access token returns that user as json', function () {
    Storage::fake('public');

    app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');

    $other = User::factory()->create([
        'name' => 'Other User',
        'email' => 'other@example.com',
    ]);

    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    $token = $user->createToken('proof')->accessToken;

    $response = $this->getJson('/api/user', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response
        ->assertOk()
        ->assertExactJson([
            'id' => $user->id,
            'email' => 'ada@example.com',
            'name' => 'Ada Lovelace',
        ]);

    expect($response->json('id'))->not->toBe($other->id);
});
