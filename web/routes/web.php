<?php

use App\Http\Controllers\Auth\AzureController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect(RouteServiceProvider::HOME);
});

Route::prefix('auth')->name('socialite.')->middleware(['guest'])->group(function () {
    Route::get('/redirect', [AzureController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [AzureController::class, 'callback'])->name('callback');
});