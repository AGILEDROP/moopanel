<?php

use App\Http\Controllers\AdminPresetController;
use Illuminate\Support\Facades\Route;

Route::post('{instance_id}/admin_preset', [AdminPresetController::class, 'store']);
