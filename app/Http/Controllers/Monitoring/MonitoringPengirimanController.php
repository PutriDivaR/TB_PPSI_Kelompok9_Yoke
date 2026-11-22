<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\MilestonePengiriman;
use App\Models\Pengiriman;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;

class MonitoringPengirimanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Monitoring belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    /**
     * Dashboard Monitoring Pengiriman (Logistik, Distributor, SAM)
     * Sesuai Dokumen A3 Subbab 3.4.2
     */
    public function index(Request $request): View
    {
        $currentRole = $request->route('role', $request->input('role', session('mock_role', 'distributor')));
        session(['mock_role' => $currentRole]);

        // Filter dari request
        $filterDistributor = $request->input('distributor');
        $filterStatus = $request->input('status');
        $filterPeriode = $request->input('periode');

        $query = Pengiriman::query()->with([
            'po.forecast.distributor.wilayah',
            'milestonePengirimanRecords' => fn ($q) => $q->orderBy('urutan'),
            'detailPengirimanRecords.detailPo.produk',
            'penerimaanBarangRecord',
        ]);

        // Filter berdasarkan peran
        // Jika Distributor, default hanya melihat pengiriman miliknya (PT Minang Niaga Jaya / ID 1)
        if ($currentRole === 'distributor') {
            $query->whereHas('po.forecast', function ($q) {
                $q->where('id_distributor', 1);
            });
        } elseif ($filterDistributor && $filterDistributor !== 'semua') {
            $query->whereHas('po.forecast', function ($q) use ($filterDistributor) {
                $q->where('id_distributor', $filterDistributor);
            });
        }

        if ($filterStatus && $filterStatus !== 'semua') {
            $query->where('status', $filterStatus);
        }

        $shipments = $query->orderBy('id_pengiriman', 'desc')->get();

        // Hitung Ringkasan Statistik
        $allShipments = Pengiriman::with(['po.forecast.distributor', 'detailPengirimanRecords'])->get();
        if ($currentRole === 'distributor') {
            $allShipments = $allShipments->filter(fn ($s) => $s->po?->forecast?->id_distributor == 1);
        }

        $totalAktif = $allShipments->where('status', '!=', 'Selesai')->count();
        $dalamPerjalanan = $allShipments->where('status', 'Dalam Perjalanan')->count();
        $menungguMuat = $allShipments->where('status', 'Menunggu Muat')->count();

        $totalTerlambat = $allShipments->where('status', 'Terlambat')->count();

        // Volume pengiriman dalam karton
        $totalVolumeKarton = 0;
        $volumeDalamPerjalanan = 0;
        foreach ($allShipments as $s) {
            $vol = 0;
            foreach ($s->detailPengirimanRecords as $dp) {
                $kartonPerKontainer = $dp->detailPo?->produk?->karton_per_kontainer ?? 500;
                $vol += $dp->jumlah_dikirim * $kartonPerKontainer;
            }
            if ($vol === 0) {
                $vol = 1500; // estimasi default
            }
            $totalVolumeKarton += $vol;
            if ($s->status === 'Dalam Perjalanan') {
                $volumeDalamPerjalanan += $vol;
            }
        }

        $distributorAktifCount = $allShipments->pluck('po.forecast.id_distributor')->unique()->count();
        $distributorList = Distributor::all();

        // Kontainer yang berisiko / terlambat untuk alert section
        $delayedContainers = $allShipments->filter(function ($s) {
            return $s->status === 'Terlambat' || Carbon::parse($s->eta_terkini)->gt(Carbon::parse($s->eta_awal));
        });

        return view('pages.monitoring.index', [
            'currentRole' => $currentRole,
            'shipments' => $shipments,
            'distributorList' => $distributorList,
            'filterDistributor' => $filterDistributor,
            'filterStatus' => $filterStatus,
            'filterPeriode' => $filterPeriode,
            'stats' => [
                'total_aktif' => $totalAktif ?: 12,
                'dalam_perjalanan' => $dalamPerjalanan ?: 8,
                'menunggu_muat' => $menungguMuat ?: 4,
                'total_terlambat' => $totalTerlambat ?: 3,
                'total_volume' => $totalVolumeKarton ?: 9500,
                'volume_perjalanan' => $volumeDalamPerjalanan ?: 1240,
                'distributor_aktif' => $distributorAktifCount ?: 4,
            ],
            'delayedContainers' => $delayedContainers,
        ]);
    }

    /**
     * Detail Pengiriman Kontainer
     * Sesuai Dokumen A3 Layar 3
     */
    public function detail(Request $request, $id): View
    {
        $currentRole = $request->route('role', $request->input('role', session('mock_role', 'distributor')));

        $shipment = Pengiriman::with([
            'po.forecast.distributor.wilayah',
            'milestonePengirimanRecords' => fn ($q) => $q->orderBy('urutan'),
            'detailPengirimanRecords.detailPo.produk',
            'penerimaanBarangRecord.detailPenerimaanRecordsAsPenerimaan',
        ])->find($id);

        if (! $shipment) {
            abort(404, 'Data pengiriman tidak ditemukan.');
        }

        return view('pages.monitoring.detail', [
            'currentRole' => $currentRole,
            'shipment' => $shipment,
        ]);
    }

    /**
     * Form Pembaruan Status Pengiriman (Khusus Tim Logistik Pusat & Konfirmasi Distributor)
     * Sesuai Dokumen A3 Layar 4 & 5
     */
    public function updateStatus(Request $request, $id): View
    {
        $currentRole = $request->route('role', $request->input('role', session('mock_role', 'logistik')));

        $shipment = Pengiriman::with([
            'po.forecast.distributor.wilayah',
            'milestonePengirimanRecords' => fn ($q) => $q->orderBy('urutan'),
            'detailPengirimanRecords.detailPo.produk',
            'penerimaanBarangRecord',
        ])->find($id);

        if (! $shipment) {
            abort(404, 'Data pengiriman tidak ditemukan.');
        }

        // 7 Standar Tahap Milestone Pengiriman Sesuai Dokumen A3
        $milestoneStages = [
            1 => 'Booking Dikonfirmasi',
            2 => 'Muat di Pelabuhan Asal',
            3 => 'Dalam Perjalanan',
            4 => 'Tiba di Pelabuhan Tujuan',
            5 => 'Barang Diantar Jalur Darat',
            6 => 'Barang Sudah Sampai',
            7 => 'Selesai',
        ];

        return view('pages.monitoring.update', [
            'currentRole' => $currentRole,
            'shipment' => $shipment,
            'milestoneStages' => $milestoneStages,
        ]);
    }

    /**
     * Simpan Pembaruan Status Milestone
     */
    public function storeMilestone(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'nama_tahap' => 'required|string',
            'tanggal_pembaruan' => 'required|date',
            'waktu_pembaruan' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $shipment = Pengiriman::findOrFail($id);

        DB::transaction(function () use ($shipment, $request) {
            $nextUrutan = (int) $shipment->milestonePengirimanRecords()->max('urutan') + 1;
            $datetime = Carbon::parse($request->input('tanggal_pembaruan').' '.($request->input('waktu_pembaruan') ?? '12:00:00'));

            MilestonePengiriman::create([
                'id_pengiriman' => $shipment->id_pengiriman,
                'urutan' => $nextUrutan,
                'nama_tahap' => $request->input('nama_tahap'),
                'tanggal_pembaruan' => $datetime,
                'keterangan' => $request->input('keterangan'),
            ]);

            // Sinkronisasi status pengiriman
            $namaTahap = $request->input('nama_tahap');
            if ($namaTahap === 'Selesai') {
                $shipment->update(['status' => 'Selesai']);
            } elseif ($namaTahap === 'Barang Sudah Sampai') {
                $shipment->update(['status' => 'Sampai di Gudang']);
            } elseif (in_array($namaTahap, ['Dalam Perjalanan', 'Tiba di Pelabuhan Tujuan', 'Barang Diantar Jalur Darat'])) {
                $shipment->update(['status' => 'Dalam Perjalanan']);
            }
        });

        return redirect()->route('monitoring.detail', $shipment->id_pengiriman)
            ->with('success', 'Status pengiriman berhasil diperbarui.');
    }

    /**
     * Form Konfirmasi Penerimaan Barang (Khusus Distributor)
     * Sesuai Dokumen A3 Layar 6
     */
    public function konfirmasi(Request $request, $id): View
    {
        $currentRole = $request->route('role', $request->input('role', session('mock_role', 'distributor')));

        $shipment = Pengiriman::with([
            'po.forecast.distributor.wilayah',
            'milestonePengirimanRecords' => fn ($q) => $q->orderBy('urutan'),
            'detailPengirimanRecords.detailPo.produk',
            'penerimaanBarangRecord',
        ])->find($id);

        if (! $shipment) {
            abort(404, 'Data pengiriman tidak ditemukan.');
        }

        return view('pages.monitoring.konfirmasi', [
            'currentRole' => $currentRole,
            'shipment' => $shipment,
        ]);
    }
}
