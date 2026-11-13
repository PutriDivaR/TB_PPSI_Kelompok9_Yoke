<?php

use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\EPO\DistributorEPOController;
use App\Http\Controllers\EPO\LogistikEPOController;
use App\Http\Controllers\PelaporanController;
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

Route::get('/simulasi/admin', function () {
    session(['mock_role' => 'admin']);

    return redirect()->route('admin.ringkasan');
})->defaults('role', 'admin')->name('simulation.admin');

// ==================== PORTAL ADMIN (Sesuai Dokumen & Mockup) ====================
Route::prefix('admin')->name('admin.')->controller(AdminPortalController::class)->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.ringkasan');
    })->name('index');
    Route::get('/ringkasan', 'ringkasan')->name('ringkasan');
    Route::get('/pengguna', 'pengguna')->name('pengguna');
    Route::get('/distributor', 'distributor')->name('distributor');
    Route::get('/produk', 'produk')->name('produk');
    Route::get('/tahun-fiskal', 'tahunFiskal')->name('tahun-fiskal');
    Route::get('/log-aktivitas', 'logAktivitas')->name('log-aktivitas');
});

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
Route::get('/pelaporan', [PelaporanController::class, 'index'])->name('pelaporan.index');
Route::get('/pelaporan/ekspor', [PelaporanController::class, 'export'])->name('pelaporan.export');
Route::post('/pelaporan/konteks', [PelaporanController::class, 'context'])->name('pelaporan.context');
Route::post('/pelaporan/sell-out', [PelaporanController::class, 'storeSellOut'])->name('pelaporan.sell-out.store');
Route::post('/pelaporan/stok', [PelaporanController::class, 'storeStock'])->name('pelaporan.stok.store');
Route::get('/integrasi-database/status', [PelaporanController::class, 'databaseStatus'])->name('database.status');

// Route Master Data Admin (Redirect ke Portal Admin)
Route::get('/master-data', function () {
    return redirect()->route('admin.ringkasan');
})->name('master-data.index');
