<?php

use App\Http\Controllers\Backups\CourseBackupController;
use App\Models\Instance;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Instance backup API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Instance backup API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('backups')->group(function () {

    Route::middleware(['checkInstanceToken'])->post('courses/{instance_id}', [CourseBackupController::class, 'store']);

});
