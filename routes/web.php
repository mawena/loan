<?php

use App\Http\Controllers\PortierController;
use Illuminate\Support\Facades\Route;

// Connexion via Portier (OpenID Connect), 404 si PORTIER_ACTIF=false
Route::get('auth/portier', [PortierController::class, 'depart'])->name('portier.depart');
Route::get('auth/portier/retour', [PortierController::class, 'retour'])->name('portier.retour');

Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');