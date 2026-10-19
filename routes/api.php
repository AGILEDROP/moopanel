<?php

use Illuminate\Support\Facades\Route;

Route::prefix('backups')->group(__DIR__.'/api/backups.php');

Route::prefix('instances')
    ->middleware('checkInstanceToken')
    ->group(__DIR__.'/api/instances.php');

Route::prefix('updates')
    ->middleware('checkInstanceToken')
    ->group(__DIR__.'/api/updates.php');
