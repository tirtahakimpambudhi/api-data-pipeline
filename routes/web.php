<?php

use App\Http\Controllers\ChannelController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\NamespaceController;
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
    Route::get('service', function () {
        return Inertia::render('service/index');
    })->name('service');

    // Environment
    Route::get('environments/search', [EnvironmentController::class, 'search'])->name('environments.search');
    Route::resource('environments', EnvironmentController::class);

    // Channel
    Route::get('channels/search', [ChannelController::class, 'search'])->name('channels.search');
    Route::resource('channels', ChannelController::class);
    Route::get('channel', function () {
        return Inertia::render('channel/index');
    })->name('channel');

    // Service Environment
    Route::get('service-environment', function () {
        return Inertia::render('service-environment/index');
    })->name('service-environment');

    // Configuration
    Route::get('configuration', function () {
        return Inertia::render('configuration/index');
    })->name('configuration');

    // Settting
    Route::get('settings', function () {
        return Inertia::render('settings/profile');
    })->name('settings');

});
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
