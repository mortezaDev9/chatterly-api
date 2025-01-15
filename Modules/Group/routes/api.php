<?php

declare(strict_types = 1);

use Modules\Group\Http\Controllers\GroupController;
use Modules\Group\Http\Controllers\GroupMemberController;

Route::middleware('auth:sanctum')->group(function () {
    Route::group([
        'prefix'     => 'groups',
        'as'         => 'groups.',
        'controller' => GroupController::class,
    ], function () {
        Route::get('', 'index')->name('index');
        Route::get('{group}', 'show')->name('show');
        Route::post('', 'store')->name('store');
        Route::patch('{group}', 'update')->name('update');
        Route::delete('{group}', 'destroy')->name('destroy');

        Route::post('/join', 'join')->name('join');
        Route::delete('{group}/leave', 'leave')->name('leave');

        Route::patch('{group}/transfer-ownership/{user}', 'transferOwnership')->name('transfer-ownership');

        Route::post('/mark-as-read', 'markAsRead')->name('mark-as-read');
    });

    Route::group([
        'prefix'     => 'groups/{group}/members',
        'as'         => 'groups.members.',
        'controller' => GroupMemberController::class,
    ], function () {
        Route::get('', 'index')->name('index');
        Route::post('', 'addMember')->name('store');
        Route::patch('{member}/promote-to-admin', 'promoteToAdmin')->name('promote-to-admin');
        Route::delete('{member}', 'removeMember')->name('destroy');
    });
});
