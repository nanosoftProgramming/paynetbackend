<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminClientsController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\AdminTransactionController;
use App\Http\Controllers\Api\BankController;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/clients', [AdminClientsController::class, 'index']);
Route::post('/admin/clients/{user}/toggle-activate', [AdminClientsController::class, 'toggleActivate']);
    Route::get('/admin/wallets', [WalletController::class, 'index']);
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::post('/currencies/update', [CurrencyController::class, 'updateRates']);
Route::post('/admin/wallets/{id}/status', [WalletController::class, 'changeWalletStatus']);
Route::post('/admin/transactions', [AdminTransactionController::class, 'store']);
Route::get('/admin/transactions', [AdminTransactionController::class, 'index']); // جلب كل المعاملات
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/client/banks', [BankController::class, 'getClientBanks']);
    Route::post('/client/bank/add', [BankController::class, 'store']);
    Route::post('/client/bank/update/{id}', [BankController::class, 'update']);
    Route::delete('/client/bank/delete/{id}', [BankController::class, 'destroy']);

    // مسارات الأدمن (يمكنك إضافة ميدلوير التحقق من الصلاحيات لاحقاً)
    Route::get('/admin/banks', [BankController::class, 'adminGetAllBanks']);
});