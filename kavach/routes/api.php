<?php

use App\Http\Controllers\Api\AdminLicenseApiController;
use App\Http\Controllers\Api\LicenseApiController;
use App\Http\Controllers\Api\UpdateApiController;
use App\Http\Middleware\AdminApiToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('activate', [LicenseApiController::class, 'activate']);
    Route::post('validate', [LicenseApiController::class, 'validateLicense']);
    Route::post('deactivate', [LicenseApiController::class, 'deactivate']);

    Route::post('update/check', [UpdateApiController::class, 'check']);
    Route::get('update/download/{release}/{license}', [UpdateApiController::class, 'download'])
        ->middleware('signed')
        ->name('api.update.download');
});

// Token-secured admin API (for the MTP Suite store and other trusted apps).
Route::prefix('admin')->middleware(AdminApiToken::class)->group(function () {
    Route::post('license', [AdminLicenseApiController::class, 'issue']);
});
