<?php

use App\Http\Controllers\NamespaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('namespaces', NamespaceController::class);

    Route::post('namespaces/{namespace}/services', [NamespaceController::class, 'storeService'])
        ->name('namespaces.services.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
