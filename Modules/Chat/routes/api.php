<?php

declare(strict_types = 1);

use Modules\Chat\Http\Controllers\ChatController;

Route::group([
    'prefix'     => 'chats',
    'as'         => 'chats.',
    'middleware' => ['auth:sanctum'],
    'controller' => ChatController::class,
], function () {
    Route::get('', 'index')->name('index');
    Route::post('', 'store')->name('store');
    Route::get('{chat}', 'show')->name('show');
    Route::delete('{chat}', 'destroy')->name('destroy');

    Route::patch('{chat}/mark-as-read', 'markAsRead')->name('mark-as-read');
});
