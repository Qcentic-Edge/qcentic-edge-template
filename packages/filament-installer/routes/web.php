<?php

use Illuminate\Support\Facades\Route;
use Mamenein\FilamentInstaller\Http\Controllers\InstallController;

Route::middleware('web')->group(function (): void {
    $path = trim((string) config('installer.path', 'install'), '/');

    Route::get($path, [InstallController::class, 'show'])->name('installer.show');
    Route::post($path, [InstallController::class, 'run'])->name('installer.run');
    Route::post($path.'/check', [InstallController::class, 'check'])->name('installer.check');
});
