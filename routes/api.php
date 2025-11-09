<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\LetterController;

// Public
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ===== Surat (semua user login) =====
    Route::get('/surat/next-number', [LetterController::class, 'nextNumber']); 
    Route::get('/surat', [LetterController::class, 'index']);
    Route::get('/surat/{id}', [LetterController::class, 'show'])->whereNumber('id');   
    Route::post('/surat', [LetterController::class, 'store']);

    Route::get('/surat/{id}/download', [LetterController::class, 'download'])
        ->name('api.surat.download');

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('/surat/{id}', [LetterController::class, 'update'])->whereNumber('id');
        Route::delete('/surat/{id}', [LetterController::class, 'destroy'])->whereNumber('id');

        Route::prefix('users')->group(function () {
            Route::get('/', [UserManagementController::class, 'index']);
            Route::post('/', [UserManagementController::class, 'store']);
            Route::get('/{id}', [UserManagementController::class, 'show'])->whereNumber('id');
            Route::put('/{id}', [UserManagementController::class, 'update'])->whereNumber('id');
            Route::delete('/{id}', [UserManagementController::class, 'destroy'])->whereNumber('id');
        });
    });
});
