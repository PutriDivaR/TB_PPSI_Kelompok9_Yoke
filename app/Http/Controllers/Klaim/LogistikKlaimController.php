<?php

namespace App\Http\Controllers\Klaim;

use App\Http\Controllers\Controller;
use App\Models\DetailKlaim;
use App\Models\Klaim;
use App\Models\Pengguna;
use App\Models\TahunFiskal;
use App\Services\KlaimService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

class LogistikKlaimController extends Controller implements HasMiddleware
{
    public function __construct(private KlaimService $klaimService) {}

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Klaim belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function proses(): View
    {
        $hariIni = now()->startOfDay();
        $tahunFiskal = TahunFiskal::query()->whereDate('tanggal_mulai', '<=', $hariIni)->whereDate('tanggal_selesai', '>=', $hariIni)->first();
        $kotakMasuk = Klaim::query()->where('status', KlaimService::STATUS_DI_LOGISTIK)
            ->with([
                'penerimaan.pengiriman.po.forecast.distributor.wilayah',
                'detailKlaimRecords.detailPenerimaan.detailPengiriman.detailPo.produk',
                'beritaAcaraKerusakanRecord.pengesah',
            ])
            ->get()->sortBy(fn (Klaim $klaim) => $klaim->beritaAcaraKerusakanRecord?->tanggal_pengesahan)->values()
            ->map(fn (Klaim $klaim) => $this->dokumen($klaim));

        return view('pages.klaim.logistik.proses', [
            'kotakMasuk' => $kotakMasuk,
            'bentukPenyelesaian' => KlaimService::BENTUK_PENYELESAIAN,
            'tahunFiskal' => $tahunFiskal,
            'stats' => [
                'belum_diproses' => $kotakMasuk->count(),
                'selesai_bulan_ini' => Klaim::query()->where('status', KlaimService::STATUS_SELESAI)
                    ->whereBetween('tanggal_penyelesaian', [$hariIni->copy()->startOfMonth(), $hariIni->copy()->endOfMonth()])->count(),
                // Klaim yang sudah sampai di Logistik dihitung dengan jumlah hasil verifikasi SAM.
                'karton_tahun_fiskal' => $tahunFiskal ? (int) DetailKlaim::query()
                    ->whereHas('klaim', fn ($query) => $query->whereIn('status', [KlaimService::STATUS_DI_LOGISTIK, KlaimService::STATUS_SELESAI])
                        ->whereBetween('tanggal_pengajuan', [$tahunFiskal->tanggal_mulai->copy()->startOfDay(), $tahunFiskal->tanggal_selesai->copy()->endOfDay()]))
                    ->sum('jumlah_terverifikasi') : 0,
            ],
        ]);
    }

    public function selesaikan(Request $request, Klaim $klaim): RedirectResponse
    {
        $nilai = $request->validate([
            'bentuk_penyelesaian' => ['required', Rule::in(KlaimService::BENTUK_PENYELESAIAN)],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], ['bentuk_penyelesaian.required' => 'Pilih bentuk penyelesaian klaim.']);
        $this->klaimService->selesaikan($klaim, $nilai['bentuk_penyelesaian'], $nilai['catatan'] ?? null, $this->logistikAktif());

        return redirect()->route('klaim.logistik.proses')->with('success', KlaimService::kodeKlaim($klaim).' ditandai selesai.');
    }

    public function kembalikan(Request $request, Klaim $klaim): RedirectResponse
    {
        $nilai = $request->validate(['catatan' => ['required', 'string', 'max:1000']],
            ['catatan.required' => 'Tuliskan alasan pengembalian ke SAM pada kolom catatan.']);
        $this->klaimService->kembalikanKeSam($klaim, $nilai['catatan'], $this->logistikAktif());

        return redirect()->route('klaim.logistik.proses')->with('success', KlaimService::kodeKlaim($klaim).' dikembalikan ke SAM untuk diverifikasi ulang.');
    }

    /** @return array<string, mixed> */
    private function dokumen(Klaim $klaim): array
    {
        $distributor = $klaim->penerimaan->pengiriman->po->forecast->distributor;
        $bak = $klaim->beritaAcaraKerusakanRecord;

        return [
            'id' => $klaim->id_klaim,
            'kode_bak' => $bak?->nomor_bak ?? '-',
            'kode_klaim' => KlaimService::kodeKlaim($klaim),
            'distributor' => $distributor->nama,
            'kota' => $distributor->wilayah?->nama_wilayah,
            'nomor_kontainer' => $klaim->penerimaan->pengiriman->nomor_kontainer,
            'nomor_po' => $klaim->penerimaan->pengiriman->po->nomor_po,
            'tanggal_tiba' => $klaim->penerimaan->tanggal_terima,
            'tanggal_disahkan' => $bak?->tanggal_pengesahan,
            'pengesah' => $bak?->pengesah?->nama ?? '-',
            'url_bak' => $bak ? route('klaim.bak', $klaim) : null,
            'baris' => $klaim->detailKlaimRecords->map(fn (DetailKlaim $detail) => [
                'produk' => $detail->detailPenerimaan->detailPengiriman->detailPo->produk->nama_produk,
                'jenis' => $detail->jenis_kerusakan,
                'diajukan' => $detail->jumlah,
                'terverifikasi' => (int) $detail->jumlah_terverifikasi,
                'penanggung' => $detail->penanggung_jawab,
            ])->all(),
            'total_karton' => (int) $klaim->detailKlaimRecords->sum('jumlah_terverifikasi'),
        ];
    }

    private function logistikAktif(): Pengguna
    {
        $pengguna = Pengguna::query()->where('status_aktif', true)
            ->whereHas('peran', fn ($query) => $query->where('nama_peran', 'Logistik Pusat'))->orderBy('id_pengguna')->first();
        abort_unless($pengguna, 422, 'Belum ada pengguna aktif berperan Logistik Pusat.');

        return $pengguna;
    }
}
