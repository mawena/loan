<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SimulationController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

// Simulations — accès public avec quota journalier (l'auth Sanctum est détectée
// via le bearer token quand il est présent, sans être obligatoire)
Route::get('simulations/quota', [SimulationController::class, 'quota'])->name('simulations.quota');
Route::post('simulations', [SimulationController::class, 'store'])->name('simulations.store');
Route::get('shared/{token}', [SimulationController::class, 'shared'])->name('simulations.shared');

Route::middleware(['auth:sanctum', 'account.status'])->group(function () {
    Route::get('simulations', [SimulationController::class, 'index'])->name('simulations.index');
    Route::get('simulations/{simulation}', [SimulationController::class, 'show'])->name('simulations.show');
    Route::delete('simulations/{simulation}', [SimulationController::class, 'destroy'])->name('simulations.destroy');
    Route::get('simulations/{simulation}/export/{format}', [SimulationController::class, 'export'])->name('simulations.export');
    Route::post('simulations/{simulation}/share', [SimulationController::class, 'share'])->name('simulations.share');
    Route::delete('simulations/{simulation}/share', [SimulationController::class, 'unshare'])->name('simulations.unshare');
});

Route::controller(AuthController::class)->group(function () {
    Route::post('auth/login', 'login')->name('auth.login');
    Route::post('auth/register', 'register')->name('auth.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::get('data', 'data')->name('data');
            Route::delete('logout', 'logout')->name('logout');
        });

        // Toujours accessible, même si password_change_required = true
        Route::put('users/update-password', [UserController::class, 'updatePassword'])
            ->name('user.update-password');

        // Routes protégées par le middleware de statut de compte
        Route::middleware('account.status')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('permissions', PermissionController::class);
        });
    });
});
