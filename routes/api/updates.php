<?php

use App\Http\Controllers\Updates\CoreUpdateController;
use App\Http\Controllers\Updates\PluginUpdateController;
use App\Http\Controllers\Updates\PluginZipUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('core')->group(function () {
    Route::post('instance/{instance_id}', [CoreUpdateController::class, 'store']);
});

Route::prefix('zip-plugins')->group(function () {
    Route::post('instance/{instance_id}', [PluginZipUpdateController::class, 'store']);
});

Route::prefix('plugins')->group(function () {
    Route::post('instance/{instance_id}', [PluginUpdateController::class, 'store']);
});
