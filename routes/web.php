<?php

use App\Http\Controllers\EPO\DistributorEPOController;
use App\Http\Controllers\EPO\LogistikEPOController;
use Illuminate\Support\Facades\Route;

// Redirect otomatis dari http://localhost:8000/ ke E-PO Distributor
Route::get('/', function () {
    return redirect()->route('e-po.distributor.hitung-forecast');
});

// Route utama simulasi aktor 
Route::get('/distributor', [DistributorEPOController::class, 'hitungForecast'])
    ->defaults('role', 'distributor')
    ->name('simulation.distributor');

Route::get('/logistik', [LogistikEPOController::class, 'antrean'])
    ->defaults('role', 'logistik')
    ->name('simulation.logistik');

Route::get('/sam', function () {
    return view('pages.role-placeholder', [
        'role' => 'sam',
        'title' => 'Sales Area Manager',
        'description' => 'Halaman simulasi untuk verifikasi dan pelaporan area.',
    ]);
})->defaults('role', 'sam')->name('simulation.sam');

Route::get('/admin', function () {
    return view('pages.role-placeholder', [
        'role' => 'admin',
        'title' => 'Administrator',
        'description' => 'Halaman simulasi untuk pengelolaan pengguna dan master data.',
    ]);
})->defaults('role', 'admin')->name('simulation.admin');

Route::prefix('e-po')->name('e-po.')->group(function () {
    // ---------------- DISTRIBUTOR ----------------
    Route::prefix('distributor')->name('distributor.')->controller(DistributorEPOController::class)->group(function () {
        Route::get('/hitung-forecast', 'hitungForecast')->name('hitung-forecast')->defaults('role', 'distributor');  // Layar 1
        Route::get('/ajukan-forecast', 'ajukanForecast')->name('ajukan-forecast')->defaults('role', 'distributor');  // Layar 2
        Route::post('/ajukan-forecast', 'submitForecast')->name('submit-forecast')->defaults('role', 'distributor');
        Route::get('/input-epo', 'inputEpo')->name('input-epo')->defaults('role', 'distributor');                    // Layar 5
        Route::post('/input-epo', 'submitEpo')->name('submit-epo')->defaults('role', 'distributor');
        Route::get('/epo-selesai', 'epoSelesai')->name('epo-selesai')->defaults('role', 'distributor');              // Layar 7
    });

    // ---------------- LOGISTIK PUSAT ----------------
    Route::prefix('logistik')->name('logistik.')->controller(LogistikEPOController::class)->group(function () {
        Route::get('/antrean', 'antrean')->name('antrean')->defaults('role', 'logistik');                         // Layar 3
        Route::get('/forecast/{id}', 'detailForecast')->name('detail-forecast')->defaults('role', 'logistik');    // Layar 4
        Route::post('/forecast/{id}/tetapkan', 'tetapkanKuota')->name('tetapkan-kuota')->defaults('role', 'logistik');
        Route::get('/epo/{id}/verifikasi', 'verifikasiEpo')->name('verifikasi-epo')->defaults('role', 'logistik'); // Layar 6
        Route::post('/epo/{id}/setujui', 'setujuiEpo')->name('setujui-epo')->defaults('role', 'logistik');
    });
});

// Route Sementara Modul Monitoring (PJ: Riski/Tamuzaki)
Route::get('/monitoring', function () {
    return view('pages.monitoring.index');
})->name('monitoring.index');

// Route Sementara Modul Klaim (PJ: Farhan)
Route::get('/klaim', function () {
    return view('pages.klaim.index');
})->name('klaim.index');

// Route Sementara Modul Pelaporan (PJ: Hasbi)
Route::get('/pelaporan', function () {
    return view('pages.pelaporan.index');
})->name('pelaporan.index');

// Route Sementara Modul Master Data Admin (PJ: Suci)
Route::get('/master-data', function () {
    return view('pages.admin.index');
})->name('admin.index');
