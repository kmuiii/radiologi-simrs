<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TindakanHasilController;
use App\Http\Controllers\DashboardController;
// use App\Http\Controllers\DetailTindakanController;

Route::view('/', 'welcome')->name('home');
// routes/web.php
Route::middleware('auth')->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'role:admin,radiologist'])->group(function () {
    Route::prefix('radiology/{detailTindakan}')->group(function () {
        Route::get('/', [TindakanHasilController::class, 'show'])->name('radiology.show');
        Route::post('/findings', [TindakanHasilController::class, 'saveFindings'])->name('radiology.findings.save');
        Route::post('/impression/generate', [TindakanHasilController::class, 'generateImpression'])->name('radiology.impression.generate');
        Route::post('/impression', [TindakanHasilController::class, 'saveImpression'])->name('radiology.impression.save');
        Route::post('/review', [TindakanHasilController::class, 'reviewReport'])->name('radiology.review');
    });
});

// Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
//     Route::resource('doctors', DoctorController::class);
// });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::prefix('radiology/{detailTindakan}')->group(function () {
    Route::get('/', [TindakanHasilController::class, 'show'])->name('radiology.show');
    Route::post('/findings', [TindakanHasilController::class, 'saveFindings'])->name('radiology.findings.save');
    Route::post('/impression/generate', [TindakanHasilController::class, 'generateImpression'])->name('radiology.impression.generate');
    Route::post('/impression', [TindakanHasilController::class, 'saveImpression'])->name('radiology.impression.save');
    Route::post('/review', [TindakanHasilController::class, 'reviewReport'])->name('radiology.review');
});

require __DIR__.'/settings.php';
