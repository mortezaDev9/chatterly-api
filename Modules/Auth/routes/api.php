<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\RegisterUserController;

Route::group([
    'prefix'     => 'v1',
    'middleware' => 'auth:sanctum',
    'controller' => RegisterUserController::class,
], function () {
    Route::post('/register', 'register')->name('register');
});
