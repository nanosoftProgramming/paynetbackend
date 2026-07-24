<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\UserTransactionController;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        
        // مسارات عامة (بدون تسجيل دخول)
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('register', [AuthController::class, 'register']);
            Route::post('login', [AuthController::class, 'login']);
        });

        // مسارات محمية تتطلب تسجيل الدخول (للمستخدمين العاديين والأدمن)
        Route::middleware(['auth:sanctum'])->group(function () {
            // معلومات المستخدم والتوثيق
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('update-profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);

            // المحفظة والمعاملات الخاصة بالمستخدم
            Route::get('/user/wallet', [WalletController::class, 'myWallet']);
            Route::get('/user/transactions', [UserTransactionController::class, 'myTransactions']);
            Route::post('/user/wallet/create', [WalletController::class, 'createMyWallet']);
            
            // مسار قبول أو رفض المعاملة للمستخدم
            Route::post('/my-transactions/{id}/respond', [UserTransactionController::class, 'updateTransactionStatus']);

            // مسارات خاصة بالأدمن فقط (تأكد أن الـ Controller يتحقق من الأدمن هنا فقط)
            Route::post('/admin/users/{id}/ip', [AuthController::class, 'updateOrCreateUserIp']);
        });

    });

});