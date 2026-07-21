<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminClientsController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/clients', [AdminClientsController::class, 'index']);
});