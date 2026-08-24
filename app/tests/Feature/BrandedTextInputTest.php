<?php

use App\Filament\Forms\Components\BrandedTextInput;
use App\Filament\Resources\Users\Pages\EditUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;

uses(DatabaseMigrations::class);

test('branded headline field is present on the user form', function () {
    $admin = actingAsRole('super_admin');

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->assertSuccessful()
        ->assertFormFieldExists(
            'headline',
            fn ($field): bool => $field instanceof BrandedTextInput,
        );
});

test('super_admin can save a valid headline on the user form', function () {
    $admin = actingAsRole('super_admin');

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm([
            'headline' => 'Staff engineer',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($admin->fresh()->headline)->toBe('Staff engineer');
});

test('user form rejects a headline longer than 80 characters', function () {
    $admin = actingAsRole('super_admin');

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm([
            'headline' => str_repeat('x', 81),
        ])
        ->call('save')
        ->assertHasFormErrors(['headline']);

    expect($admin->fresh()->headline)->toBeNull();
});

test('user without permission cannot open the user form', function () {
    $target = seedUser();
    actingAsRole('user');

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->assertForbidden();
});
