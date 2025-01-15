<?php

declare(strict_types = 1);

use Modules\Search\Http\Controllers\SearchController;

Route::group([
    'prefix'     => 'search',
    'as'         => 'search.',
    'middleware' => ['auth:sanctum'],
    'controller' => SearchController::class,
], function () {
    Route::get('', 'search')->name('index');
});
