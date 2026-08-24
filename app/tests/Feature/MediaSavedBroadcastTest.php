<?php

use App\Events\MediaSaved;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
});

test('attaching media dispatches a should-broadcast event on the owner private channel', function () {
    Event::fake([MediaSaved::class]);

    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('photo.jpg'))
        ->toMediaCollection('avatar');

    Event::assertDispatched(MediaSaved::class, function (MediaSaved $event) use ($user) {
        if (! $event instanceof ShouldBroadcast) {
            return false;
        }

        if ((int) $event->media->model_id !== (int) $user->id) {
            return false;
        }

        if ($event->media->file_name !== 'photo.jpg') {
            return false;
        }

        $channels = collect($event->broadcastOn());

        return $channels->contains(
            fn ($channel) => $channel instanceof PrivateChannel
                && $channel->name === 'private-App.Models.User.'.$user->id
        );
    });
});

test('user keeps HasRoles and media collections', function () {
    expect(class_uses_recursive(User::class))->toContain(HasRoles::class, InteractsWithMedia::class);

    $user = User::factory()->create();

    expect($user->getRegisteredMediaCollections()->pluck('name')->all())
        ->toContain('avatar', 'uploads');
});
