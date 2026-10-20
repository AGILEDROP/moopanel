<?php

use App\Http\Controllers\Auth\AzureController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('admin');
});

Route::prefix('auth')->name('socialite.')->middleware(['guest'])->group(function () {
    Route::get('/redirect', [AzureController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [AzureController::class, 'callback'])->name('callback');
});
