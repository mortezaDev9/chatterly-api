<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\EmailVerificationController;
use Modules\Auth\Http\Controllers\ForgotPasswordController;
use Modules\Auth\Http\Controllers\RegisterUserController;
use Modules\Auth\Http\Controllers\AuthenticateUserController;
use Modules\Auth\Http\Controllers\ResetPasswordController;

Route::group(['middleware' => 'guest'], function () {
    Route::get('/register', [RegisterUserController::class, 'showRegistrationForm'])
        ->name('register.form');
    Route::post('/register', [RegisterUserController::class, 'register'])
        ->name('register');

    Route::get('/login', [AuthenticateUserController::class, 'showLoginForm'])
        ->name('login.form');
    Route::post('/login', [AuthenticateUserController::class, 'login'])
        ->name('login');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])
        ->name('forgot-password.form');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetPasswordEmail'])
        ->name('forgot-password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetPasswordForm'])
        ->name('reset-password.form');
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
        ->name('reset-password');
});

Route::group(['middleware' => 'auth'], function () {
    Route::post('/logout', [AuthenticateUserController::class, 'logout'])
        ->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'showVerificationNotice'])
        ->middleware('unverified')
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['unverified' ,'signed'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resendVerificationEmail'])
        ->middleware(['unverified', 'throttle:6,1'])
        ->name('verification.resend');
});
