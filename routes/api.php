<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        // 6 attempts per minute per IP — brute-force protection
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('register', [AuthController::class, 'register']);
            Route::post('login', [AuthController::class, 'login']);
        });
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/wallet', [WalletController::class, 'myWallet']);
    Route::post('/user/wallet/create', [WalletController::class, 'createMyWallet']);
                Route::post('/admin/users/{id}/ip', [AuthController::class, 'updateOrCreateUserIp']);

});
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('update-profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });
    });

});
