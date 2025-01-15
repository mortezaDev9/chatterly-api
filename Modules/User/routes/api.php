<?php

use Modules\User\Http\Controllers\UserController;

Route::group([
    'prefix'     => 'users',
    'as'         => 'users.',
    'middleware' => ['auth:sanctum'],
    'controller' => UserController::class
], function () {
    Route::get('blocked', 'blockedUsers');

    Route::post('block', 'blockUser');
    Route::post('unblock', 'unblockUser');
});
