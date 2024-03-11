<?php

use App\SCIM\src\Controllers\ResourceController;
use App\SCIM\src\Controllers\ResourceTypeController;
use App\SCIM\src\Controllers\SchemaController;
use App\SCIM\src\Controllers\ServiceProviderController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('azureprovisioning-accounts.routePrefix'),
    'middleware' => array_merge([SubstituteBindings::class], config('azureprovisioning-accounts.routeMiddleware')),
], function () {
    Route::get('/ServiceProviderConfig', [ServiceProviderController::class, 'index']);
    Route::get('/Schemas', [SchemaController::class, 'index']);
    Route::get('/ResourceTypes', [ResourceTypeController::class, 'index']);

    Route::get('/{overwrittenResourceType}', [ResourceController::class, 'index'])
        ->name('AccountsAzureProvisioning.Resources');

    Route::post('/{overwrittenResourceType}', [ResourceController::class, 'create'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::get('/{overwrittenResourceType}/{overwrittenResourceObject}', [ResourceController::class, 'show'])
        ->name('AccountsAzureProvisioning.Resource');

    Route::patch('/{overwrittenResourceType}/{overwrittenResourceObject}', [ResourceController::class, 'update'])
        ->name('AccountsAzureProvisioning.Resource.Update')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::put('/{overwrittenResourceType}/{overwrittenResourceObject}', [ResourceController::class, 'replace'])
        ->name('AccountsAzureProvisioning.Resource.Replace')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::delete('/{overwrittenResourceType}/{overwrittenResourceObject}', [ResourceController::class, 'delete'])
        ->name('AccountsAzureProvisioning.Resource.Delete')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::get('/Schemas/{id}', function ($id) {
        return $id;
    })->name('AccountsAzureProvisioning.Schemas');
    Route::get('/ResourceTypes/{id}', function ($id) {
        return $id;
    })->name('AccountsAzureProvisioning.ResourceType');
});
