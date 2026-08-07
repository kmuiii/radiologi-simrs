<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpertisePdfController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

// Central dashboard entry that redirects users based on their role
Route::middleware('auth')->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::livewire('/users', 'admin.users.index')->name('users.index');
    Route::livewire('/configuration', 'admin.configuration.index')->name('configuration.index');

    Route::prefix('master')->name('master.')->group(function () {
        Route::livewire('/examination-types', 'admin.master.examination-types.index')->name('examination-types.index');
        Route::livewire('/report-master-templates', 'admin.master.report-master-templates.index')->name('report-master-templates.index');
        Route::livewire('/master-poli', 'admin.master.master-poli.index')->name('master-poli.index');
    });

    Route::prefix('pendaftaran')->name('pendaftaran.')->group(function () {
        Route::livewire('/registrasi', 'admin.pendaftaran.registrasi.index')->name('registrasi.index');
        Route::livewire('/tindakan', 'admin.pendaftaran.tindakan.index')->name('tindakan.index');
        Route::livewire('/tindakan/create', 'admin.pendaftaran.tindakan.create')->name('tindakan.create');
    });
});

// Radiologist area
Route::middleware(['auth', 'role:admin,radiologist'])->prefix('radiology')->name('radiology.')->group(function () {
    Route::livewire('/dashboard', 'radiology.dashboard')->name('dashboard');
    Route::livewire('/tindakan', 'admin.pendaftaran.tindakan.index')->name('tindakan.index');
    Route::livewire('/tindakan/create', 'admin.pendaftaran.tindakan.create')->name('tindakan.create');
    Route::livewire('/pemeriksaan/{tindakan}', 'radiology.pemeriksaan.show')->name('pemeriksaan.show');

    // Export expertise PDF per detail tindakan
    Route::get('/pemeriksaan/{tindakan}/expertise/{detail}/pdf', [ExpertisePdfController::class, 'download'])
        ->name('expertise.pdf');

    // Export expertise PDF semua baris (Cetak Semua)
    Route::get('/pemeriksaan/{tindakan}/expertise-all/pdf', [ExpertisePdfController::class, 'all'])
        ->name('expertise.all.pdf');
});

require __DIR__.'/settings.php';
