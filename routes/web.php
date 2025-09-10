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
        return Inertia::render('namespace/index');
    }) ->name('namespace');
    Route::get('service', function () {
        return Inertia::render('service/index');
    }) ->name('service');
    Route::get('environment', function () {
        return Inertia::render('environment/index');
    }) ->name('environment');
    Route::get('channel', function () {
        return Inertia::render('channel/index');
    }) ->name('channel');
    Route::get('configuration', function () {
        return Inertia::render('configuration/index');
    }) ->name('configuration');
    Route::get('settings', function () {
        return Inertia::render('settings/profile');
    }) ->name('settings');
    Route::get('service-environment', function () {
        return Inertia::render('service-environment/index');
    }) ->name('service-environment');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
