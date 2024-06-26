<?php

use App\Http\Controllers\AdminPresetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Delta reports API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Delta reports API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['checkInstanceToken'])->post('instances/{instance_id}/admin_preset', [AdminPresetController::class, 'store']);
