<?php

use App\Filament\Widgets\CountsChart;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
    $this->seed(RoleSeeder::class);
});

test('super_admin can view the counts chart widget', function () {
    actingAsRole('super_admin');

    Livewire::test(CountsChart::class)
        ->assertSuccessful()
        ->assertSee('Users and media');
});

test('guest is forbidden from the counts chart widget', function () {
    asGuest();

    Livewire::test(CountsChart::class)
        ->assertForbidden();
});

test('guest is redirected from the admin dashboard', function () {
    asGuest();

    $this->get('/admin')->assertRedirect();
});

test('media counts do not leak other users private media', function () {
    $owner = seedUser();
    countsChartPrivateMedia($owner);
    countsChartPrivateMedia($owner);

    $viewer = seedUser();
    countsChartGrantViewMedia($viewer);

    expect(CountsChart::countsFor($viewer)['media'])->toBe(0);

    countsChartPrivateMedia($viewer);

    expect(CountsChart::countsFor($viewer)['media'])->toBe(1);

    $admin = seedSuperAdmin();

    expect(CountsChart::countsFor($admin)['media'])->toBe(3);

    $this->actingAs($admin);

    $data = invade(Livewire::test(CountsChart::class)->instance())->getData();

    expect($data['datasets'][0]['data'][1])->toBe(3);
    expect($data['labels'])->toBe(['Users', 'Media']);
});

function countsChartGrantViewMedia(User $user): User
{
    Permission::findOrCreate('View:Media', 'web');
    $user->givePermissionTo('View:Media');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh() ?? $user;
}

function countsChartPrivateMedia(User $user): Media
{
    return $user->addMedia(UploadedFile::fake()->create('secret.pdf', 20, 'application/pdf'))
        ->toMediaCollection('uploads');
}
