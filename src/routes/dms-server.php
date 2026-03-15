<?php

use Arshad1114\DmsDiskServer\Http\Controllers\DmsReceiverController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('dms-disk-server.route_prefix', 'dms-disk'))
    ->middleware(['api', 'dms.server.auth'])
    ->group(function () {
        Route::post   ('upload',     [DmsReceiverController::class, 'upload']);
        Route::get    ('file',       [DmsReceiverController::class, 'download']);
        Route::delete ('file',       [DmsReceiverController::class, 'delete']);
        Route::get    ('exists',     [DmsReceiverController::class, 'exists']);
        Route::get    ('url',        [DmsReceiverController::class, 'url']);
        Route::get    ('temp-url',   [DmsReceiverController::class, 'temporaryUrl']);
        Route::post   ('move',       [DmsReceiverController::class, 'move']);
        Route::get    ('list',       [DmsReceiverController::class, 'list']);
        Route::get    ('metadata',   [DmsReceiverController::class, 'metadata']);
        Route::post   ('visibility', [DmsReceiverController::class, 'setVisibility']);
    });
