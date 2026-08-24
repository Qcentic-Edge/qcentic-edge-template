<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $user = $request->user();

    return [
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->name,
    ];
})->middleware('auth:api');
