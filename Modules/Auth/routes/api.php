<?php

declare(strict_types = 1);

use Modules\Auth\Http\Controllers\AuthenticateUserController;
use Modules\Auth\Http\Controllers\RegisterUserController;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::post('register', [RegisterUserController::class, 'register'])->name('register');
    Route::post('login', [AuthenticateUserController::class, 'login'])->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthenticateUserController::class, 'logout'])->name('logout');
    Route::post('logout-all-other-sessions', [
        AuthenticateUserController::class,
        'logoutAllOtherSessions'
    ])->name('logout.all.other.sessions');
    Route::post('logout-all', [
        AuthenticateUserController::class,
        'logoutAll'
    ])->name('logout.all');
});
