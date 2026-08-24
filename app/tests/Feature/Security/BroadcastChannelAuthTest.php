<?php

use App\Events\MediaSaved;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Testing\TestResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(DatabaseMigrations::class);

beforeEach(function () {
    // phpunit.xml pins BROADCAST_CONNECTION=null so queued events never open a
    // Reverb TCP socket. Channel auth still needs the Reverb/Pusher HMAC path.
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'laravel-reverb-key');
    config()->set('broadcasting.connections.reverb.secret', 'laravel-reverb-secret');
    config()->set('broadcasting.connections.reverb.app_id', '1001');
    config()->set('broadcasting.connections.reverb.options.host', '127.0.0.1');
    config()->set('broadcasting.connections.reverb.options.port', 9);
    config()->set('broadcasting.connections.reverb.options.scheme', 'http');
    config()->set('broadcasting.connections.reverb.options.useTLS', false);

    Broadcast::forgetDrivers();
    require base_path('routes/channels.php');
});

test('guest cannot subscribe to a private user channel', function () {
    $owner = seedUser();

    asGuest();

    $response = postBroadcastAuth($owner);

    expect($response->status())->toBeIn([401, 403]);
});

test('user cannot subscribe to another users private channel', function () {
    $owner = seedUser();
    actingAsRole('user');

    postBroadcastAuth($owner)->assertForbidden();
});

test('owner can subscribe to their private user channel', function () {
    $owner = actingAsRole('user');

    postBroadcastAuth($owner)
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

function postBroadcastAuth(User $owner): TestResponse
{
    $channel = mediaSavedPrivateChannel($owner);

    return test()->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => $channel,
    ]);
}

function mediaSavedPrivateChannel(User $owner): string
{
    $media = new Media;
    $media->setRelation('model', $owner);

    $channels = (new MediaSaved($media))->broadcastOn();

    expect($channels)->not->toBeEmpty();

    return $channels[0]->name;
}
