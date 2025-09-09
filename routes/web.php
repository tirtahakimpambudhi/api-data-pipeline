<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    Route::get('namespace', function () {
        return Inertia::render('namespace');
    }) ->name('namespace');
    Route::get('services', function () {
        return Inertia::render('services');
    }) ->name('services');
    Route::get('environments', function () {
        return Inertia::render('environments');
    }) ->name('environments');
    Route::get('channels', function () {
        return Inertia::render('channels');
    }) ->name('channels');
    Route::get('configuration', function () {
        return Inertia::render('configuration');
    }) ->name('configuration');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
