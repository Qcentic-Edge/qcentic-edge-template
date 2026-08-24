<?php

use App\Http\Controllers\MediaAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/media/{media}/signed', [MediaAccessController::class, 'signed'])
    ->middleware('signed')
    ->name('media.signed');

Route::middleware('auth')->group(function () {
    Route::patch('/media/{media}', [MediaAccessController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaAccessController::class, 'destroy'])->name('media.destroy');
});
