<?php

use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EPO\DistributorEPOController;
use App\Http\Controllers\EPO\LogistikEPOController;
use App\Http\Controllers\Klaim\DistributorKlaimController;
use App\Http\Controllers\Klaim\KlaimDokumenController;
use App\Http\Controllers\Klaim\LogistikKlaimController;
use App\Http\Controllers\Klaim\SamKlaimController;
use App\Http\Controllers\PelaporanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ==================== AUTENTIKASI ====================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::redirect('/', '/login');

Route::middleware(['auth', 'active'])->group(function () {
    Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/profil/update', [ProfileController::class, 'update'])->name('profil.update');

    // ==================== PORTAL ADMIN ====================
    Route::prefix('admin')->name('admin.')->controller(AdminPortalController::class)->middleware('role:admin')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.ringkasan');
        })->name('index');
        Route::get('/ringkasan', 'ringkasan')->name('ringkasan');

        Route::get('/pengguna', 'pengguna')->name('pengguna');
        Route::post('/pengguna', 'storePengguna')->name('pengguna.store');
        Route::get('/pengguna/{pengguna}/edit', 'editPengguna')->name('pengguna.edit');
        Route::put('/pengguna/{pengguna}', 'updatePengguna')->name('pengguna.update');
        Route::post('/pengguna/{pengguna}/toggle', 'togglePengguna')->name('pengguna.toggle');
        Route::delete('/pengguna/{pengguna}', 'deletePengguna')->name('pengguna.delete');

        Route::get('/distributor', 'distributor')->name('distributor');
        Route::post('/distributor', 'storeDistributor')->name('distributor.store');
        Route::delete('/distributor/{distributor}', 'deleteDistributor')->name('distributor.delete');

        Route::get('/produk', 'produk')->name('produk');
        Route::post('/produk', 'storeProduk')->name('produk.store');
        Route::delete('/produk/{produk}', 'deleteProduk')->name('produk.delete');

        Route::get('/tahun-fiskal', 'tahunFiskal')->name('tahun-fiskal');
        Route::post('/tahun-fiskal', 'storeTahunFiskal')->name('tahun-fiskal.store');
        Route::post('/tahun-fiskal/{tahunFiskal}/aktifkan', 'activateTahunFiskal')->name('tahun-fiskal.activate');

        Route::get('/log-aktivitas', 'logAktivitas')->name('log-aktivitas');
    });

    // ==================== MODUL E-PO & FORECASTING ====================
    Route::prefix('e-po')->name('e-po.')->group(function () {
        Route::prefix('distributor')->name('distributor.')->controller(DistributorEPOController::class)->middleware('role:distributor')->group(function () {
            Route::get('/hitung-forecast', 'hitungForecast')->name('hitung-forecast')->defaults('role', 'distributor');
            Route::get('/ajukan-forecast', 'ajukanForecast')->name('ajukan-forecast')->defaults('role', 'distributor');
            Route::post('/ajukan-forecast', 'submitForecast')->name('submit-forecast')->defaults('role', 'distributor');
            Route::delete('/forecast/{forecast}', 'hapusForecast')->whereNumber('forecast')->name('hapus-forecast')->defaults('role', 'distributor');
            Route::get('/input-epo', 'inputEpo')->name('input-epo')->defaults('role', 'distributor');
            Route::post('/input-epo', 'submitEpo')->name('submit-epo')->defaults('role', 'distributor');
            Route::delete('/epo/{po}', 'hapusEpo')->whereNumber('po')->name('hapus-epo')->defaults('role', 'distributor');
            Route::get('/epo-selesai/{po?}', 'epoSelesai')->whereNumber('po')->name('epo-selesai')->defaults('role', 'distributor');
        });

        Route::prefix('logistik')->name('logistik.')->controller(LogistikEPOController::class)->middleware('role:logistik pusat')->group(function () {
            Route::get('/antrean', 'antrean')->name('antrean')->defaults('role', 'logistik');
            Route::get('/forecast/{forecast}', 'detailForecast')->whereNumber('forecast')->name('detail-forecast')->defaults('role', 'logistik');
            Route::post('/forecast/{forecast}/mulai', 'mulaiForecast')->whereNumber('forecast')->name('mulai-forecast')->defaults('role', 'logistik');
            Route::post('/forecast/{forecast}/tetapkan', 'tetapkanKuota')->whereNumber('forecast')->name('tetapkan-kuota')->defaults('role', 'logistik');
            Route::get('/epo/{po}/verifikasi', 'verifikasiEpo')->whereNumber('po')->name('verifikasi-epo')->defaults('role', 'logistik');
            Route::post('/epo/{po}/mulai', 'mulaiEpo')->whereNumber('po')->name('mulai-epo')->defaults('role', 'logistik');
            Route::post('/epo/{po}/setujui', 'setujuiEpo')->whereNumber('po')->name('setujui-epo')->defaults('role', 'logistik');
        });
    });

    Route::get('/monitoring', function () {
        return view('pages.monitoring.index');
    })->name('monitoring.index');

    Route::prefix('klaim')->name('klaim.')->group(function () {
        Route::controller(KlaimDokumenController::class)->group(function () {
            Route::get('/foto/{foto}', 'foto')->whereNumber('foto')->name('foto');
            Route::get('/bak/{klaim}', 'bak')->whereNumber('klaim')->name('bak');
            Route::get('/bak/{klaim}/tanda-tangan', 'tandaTangan')->whereNumber('klaim')->name('tanda-tangan');
        });

        Route::prefix('distributor')->name('distributor.')->controller(DistributorKlaimController::class)->middleware('role:distributor')->group(function () {
            Route::get('/beranda', 'beranda')->name('beranda')->defaults('role', 'distributor');
            Route::get('/ajukan', 'ajukan')->name('ajukan')->defaults('role', 'distributor');
            Route::post('/ajukan', 'simpan')->name('simpan')->defaults('role', 'distributor');
            Route::get('/detail/{klaim}', 'detail')->whereNumber('klaim')->name('detail')->defaults('role', 'distributor');
        });

        Route::prefix('sam')->name('sam.')->controller(SamKlaimController::class)->middleware('role:sam')->group(function () {
            Route::get('/antrean', 'antrean')->name('antrean')->defaults('role', 'sam');
            Route::post('/jadwal/{klaim}', 'jadwalkan')->whereNumber('klaim')->name('jadwal')->defaults('role', 'sam');
            Route::get('/verifikasi/{klaim}', 'verifikasi')->whereNumber('klaim')->name('verifikasi')->defaults('role', 'sam');
            Route::post('/verifikasi/{klaim}', 'simpanVerifikasi')->whereNumber('klaim')->name('simpan-verifikasi')->defaults('role', 'sam');
            Route::post('/verifikasi/{klaim}/kembalikan', 'kembalikan')->whereNumber('klaim')->name('kembalikan')->defaults('role', 'sam');
            Route::get('/pengesahan/{klaim}', 'pengesahan')->whereNumber('klaim')->name('pengesahan')->defaults('role', 'sam');
            Route::post('/pengesahan/{klaim}', 'sahkan')->whereNumber('klaim')->name('sahkan')->defaults('role', 'sam');
        });

        Route::prefix('logistik')->name('logistik.')->controller(LogistikKlaimController::class)->middleware('role:logistik pusat')->group(function () {
            Route::get('/proses', 'proses')->name('proses')->defaults('role', 'logistik');
            Route::post('/proses/{klaim}/selesai', 'selesaikan')->whereNumber('klaim')->name('selesaikan')->defaults('role', 'logistik');
            Route::post('/proses/{klaim}/kembalikan', 'kembalikan')->whereNumber('klaim')->name('kembalikan')->defaults('role', 'logistik');
        });
    });

    Route::get('/pelaporan', [PelaporanController::class, 'index'])->name('pelaporan.index');
    Route::get('/integrasi-database/status', [PelaporanController::class, 'databaseStatus'])->name('database.status');

    Route::get('/master-data', function () {
        return redirect()->route('admin.ringkasan');
    })->middleware('role:admin')->name('master-data.index');
});
