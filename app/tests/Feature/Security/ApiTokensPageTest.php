<?php

use App\Filament\Pages\ApiTokens;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\ClientRepository;
use Livewire\Livewire;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
});

test('app panel user can open the api tokens page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(ApiTokens::getUrl(panel: 'app'))
        ->assertOk();
});

test('guest is sent to login instead of the api tokens page', function () {
    $this->get('/app/api-tokens')->assertRedirect();
});

test('user can mint a personal access token from the panel page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->actingAs($user);

    $component = Livewire::test(ApiTokens::class)
        ->callAction('createToken', ['name' => 'CI token']);

    expect($component->get('plainTextToken'))->toBeString()->not->toBeEmpty();
    expect($user->tokens()->where('name', 'CI token')->exists())->toBeTrue();
});
