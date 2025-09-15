<?php

use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\NamespaceController;
use App\Http\Controllers\ServiceEnvironmentController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Namespaces;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');


    // Namespace
    Route::get('namespaces/search', [NamespaceController::class, 'search'])->name('namespaces.search');
    Route::resource('namespaces', NamespaceController::class);
    Route::post('namespaces/{namespace}/services', [NamespaceController::class, 'storeService'])
        ->name('namespaces.services.store');

    // Service
    Route::get('services/search', [ServiceController::class, 'search'])->name('services.search');
    Route::resource('services', ServiceController::class);

    // Environment
    Route::get('environments/search', [EnvironmentController::class, 'search'])->name('environments.search');
    Route::resource('environments', EnvironmentController::class);

    // Channel
    Route::get('channels/search', [ChannelController::class, 'search'])->name('channels.search');
    Route::resource('channels', ChannelController::class);


    // Service Environment
    Route::get('service-environments/search', [ServiceEnvironmentController::class, 'search'])->name('service-environments.search');
    Route::resource('service-environments', ServiceEnvironmentController::class);


    // Configuration
    Route::get('configurations/search', [ConfigurationController::class, 'search'])->name('configurations.search');
    Route::resource('configurations', ConfigurationController::class);


    // Settting
    Route::get('settings', function () {
        return Inertia::render('settings/profile');
    })->name('settings');

});
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
