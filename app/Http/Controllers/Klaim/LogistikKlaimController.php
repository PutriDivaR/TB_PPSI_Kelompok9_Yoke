<?php

namespace App\Http\Controllers\Klaim;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class LogistikKlaimController extends Controller implements HasMiddleware
{
    /**
     * Bentuk penyelesaian klaim.
     *
     * Daftar ini masih sementara dan menunggu konfirmasi dari perusahaan.
     *
     * @var array<string, string>
     */
    private const BENTUK_PENYELESAIAN = [
        'ganti_rugi' => 'Ganti Rugi',
        'penyesuaian_kewajiban' => 'Penyesuaian Catatan Kewajiban',
    ];

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Klaim belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function proses(): View
    {
        $hariIni = CarbonImmutable::now()->startOfDay();
        // shortcut: tahun fiskal ditulis langsung, ganti dengan TahunFiskal aktif saat modul disambungkan ke database.
        $tahunFiskal = ['label' => 'FY-2026', 'mulai' => CarbonImmutable::parse('2026-01-01'), 'selesai' => CarbonImmutable::parse('2026-12-31')];
        $kotakMasuk = collect($this->contohKotakMasuk());
        $selesai = collect($this->contohKlaimSelesai());

        return view('pages.klaim.logistik.proses', [
            'kotakMasuk' => $kotakMasuk,
            'bentukPenyelesaian' => self::BENTUK_PENYELESAIAN,
            'tahunFiskal' => $tahunFiskal,
            'stats' => [
                'belum_diproses' => $kotakMasuk->count(),
                'selesai_bulan_ini' => $selesai->filter(fn (array $k) => $k['tanggal_selesai']->isSameMonth($hariIni))->count(),
                // Klaim yang sampai di Logistik: berita acara di kotak masuk ditambah klaim yang sudah selesai.
                'karton_tahun_fiskal' => $kotakMasuk->merge($selesai)
                    ->filter(fn (array $k) => $k['tanggal_pengajuan']->between($tahunFiskal['mulai'], $tahunFiskal['selesai']))
                    ->sum('total_karton'),
            ],
        ]);
    }

    /**
     * Berita acara yang sudah disahkan SAM dan menunggu diproses Logistik (data contoh).
     *
     * @return list<array{kode_bak: string, kode_klaim: string, distributor: string, kota: string, nomor_kontainer: string, nomor_po: string, tanggal_tiba: CarbonImmutable, tanggal_pengajuan: CarbonImmutable, tanggal_disahkan: CarbonImmutable, pengesah: string, baris: list<array{produk: string, jenis: string, diajukan: int, terverifikasi: int, penanggung: string}>, total_karton: int}>
     */
    private function contohKotakMasuk(): array
    {
        $rusak = fn (string $produk, string $jenis, int $diajukan, int $terverifikasi) => [
            'produk' => $produk, 'jenis' => $jenis, 'diajukan' => $diajukan, 'terverifikasi' => $terverifikasi,
            'penanggung' => DistributorKlaimController::JENIS_KERUSAKAN[$jenis]['penanggung'],
        ];
        $dokumen = [
            [9, 17, 'PT Minang Niaga Jaya', 'Padang', 'TCNU 7702154', 'PO-YFI-2026-0081', '2026-11-09', '2026-11-14', '2026-11-17', [
                $rusak('FAN Sparkling Water 325ml', 'Kurang Jumlah', 25, 25),
                $rusak('YFI Isotonik Refresh 330ml', 'Kurang Jumlah', 15, 15),
            ]],
            [8, 13, 'PT Tirta Niaga Mandiri', 'Pekanbaru', 'CAIU 7340195', 'PO-YFI-2026-0084', '2026-11-03', '2026-11-09', '2026-11-15', [
                $rusak('YFI Teh Melati 500ml', 'Penyok', 55, 52),
                $rusak('YFI Jus Apel 330ml', 'Bocor', 18, 18),
            ]],
            [6, 10, 'PT Tirta Niaga Mandiri', 'Pekanbaru', 'OOLU 2958841', 'PO-YFI-2026-0077', '2026-10-24', '2026-10-31', '2026-11-10', [
                $rusak('YFI Isotonik Refresh 330ml', 'Basah', 32, 32),
            ]],
        ];

        return array_map(fn (array $d) => [
            'kode_bak' => 'BAK-'.str_pad((string) $d[0], 5, '0', STR_PAD_LEFT),
            'kode_klaim' => 'KLM-'.str_pad((string) $d[1], 5, '0', STR_PAD_LEFT),
            'distributor' => $d[2], 'kota' => $d[3], 'nomor_kontainer' => $d[4], 'nomor_po' => $d[5],
            'tanggal_tiba' => CarbonImmutable::parse($d[6]), 'tanggal_pengajuan' => CarbonImmutable::parse($d[7]),
            'tanggal_disahkan' => CarbonImmutable::parse($d[8]), 'pengesah' => 'Farhan Fitrahadi',
            'baris' => $d[9], 'total_karton' => array_sum(array_column($d[9], 'terverifikasi')),
        ], $dokumen);
    }

    /**
     * Klaim yang sudah diselesaikan Logistik (data contoh, untuk kartu ringkasan).
     *
     * @return list<array{kode_klaim: string, tanggal_pengajuan: CarbonImmutable, tanggal_selesai: CarbonImmutable, total_karton: int}>
     */
    private function contohKlaimSelesai(): array
    {
        return array_map(fn (array $k) => [
            'kode_klaim' => 'KLM-'.str_pad((string) $k[0], 5, '0', STR_PAD_LEFT),
            'tanggal_pengajuan' => CarbonImmutable::parse($k[1]), 'tanggal_selesai' => CarbonImmutable::parse($k[2]), 'total_karton' => $k[3],
        ], [
            [15, '2026-11-03', '2026-11-12', 126],
            [12, '2026-10-13', '2026-10-24', 88],
            [11, '2026-09-28', '2026-10-09', 30],
            [9, '2026-10-22', '2026-11-05', 60],
        ]);
    }
}
