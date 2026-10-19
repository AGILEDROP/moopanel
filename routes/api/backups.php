<?php

use App\Http\Controllers\Backups\CourseBackupController;
use App\Http\Controllers\Backups\CourseBackupDeletionController;
use App\Http\Controllers\Backups\CourseBackupRestoreController;
use Illuminate\Support\Facades\Route;

Route::middleware('checkInstanceToken')->group(function () {
    Route::post('courses/{instance_id}', [CourseBackupController::class, 'store']);
    Route::post('restore/instance/{instance_id}', [CourseBackupRestoreController::class, 'restore']);
    Route::post('delete/instance/{instance_id}', [CourseBackupDeletionController::class, 'delete']);
});

Route::middleware('checkRefererToken')->group(function () {
    Route::get('course/{moodle_course_id}', [CourseBackupController::class, 'index']);
});
