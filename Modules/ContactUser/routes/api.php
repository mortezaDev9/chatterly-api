<?php

declare(strict_types = 1);


use Modules\ContactUser\Http\Controllers\ContactUserController;

Route::group([
    'prefix'     => 'contacts',
    'as'         => 'contacts.',
    'middleware' => ['auth:sanctum'],
    'controller' => ContactUserController::class,
], function () {
    Route::get('', 'index')->name('index');
    Route::get('{contact}', 'show')->name('show');
    Route::post('', 'store')->name('store');
    Route::patch('{contact}', 'update')->name('update');
    Route::delete('{contact}', 'destroy')->name('destroy');
});
