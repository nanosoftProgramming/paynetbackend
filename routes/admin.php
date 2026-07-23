<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminClientsController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\AdminTransactionController;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/clients', [AdminClientsController::class, 'index']);
Route::post('/admin/clients/{user}/toggle-activate', [AdminClientsController::class, 'toggleActivate']);
    Route::get('/admin/wallets', [WalletController::class, 'index']);
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::post('/currencies/update', [CurrencyController::class, 'updateRates']);
Route::post('/admin/wallets/{id}/status', [WalletController::class, 'changeWalletStatus']);
Route::post('/admin/transactions', [AdminTransactionController::class, 'store']);
});