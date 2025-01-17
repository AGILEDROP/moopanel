<?php

use App\Http\Controllers\Updates\CoreUpdateController;
use App\Http\Controllers\Updates\PluginUpdateController;
use App\Http\Controllers\Updates\PluginZipUpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Instance updates API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Instance updates API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// middleware(['checkInstanceToken'])->

Route::prefix('updates')->group(function () {

    Route::prefix('core')->group(function () {

        Route::middleware(['checkInstanceToken'])->post('instance/{instance_id}', [CoreUpdateController::class, 'store']);
    });

    Route::prefix('zip-plugins')->group(function () {

        Route::middleware(['checkInstanceToken'])->post('instance/{instance_id}', [PluginZipUpdateController::class, 'store']);
    });

    Route::prefix('plugins')->group(function () {

        Route::middleware(['checkInstanceToken'])->post('instance/{instance_id}', [PluginUpdateController::class, 'store']);
    });
});
