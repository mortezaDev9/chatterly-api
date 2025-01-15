<?php

declare(strict_types = 1);

use Modules\Device\Http\Controllers\DeviceController;

Route::group([
    'prefix'     => 'devices',
    'as'         => 'devices.',
    'middleware' => ['auth:sanctum'],
    'controller' => DeviceController::class,
], function () {
    Route::get('', 'index')->name('index');
});
