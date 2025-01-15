<?php

declare(strict_types = 1);

use Modules\Message\Http\Controllers\MessageController;

Route::group([
    'prefix'     => 'messages',
    'as'         => 'messages.',
    'middleware' => ['auth:sanctum'],
    'controller' => MessageController::class,
], function () {
    Route::post('', 'store')->name('store');
    Route::patch('{message}', 'update')->name('update');
    Route::delete('{message}', 'destroy')->name('destroy');
});
