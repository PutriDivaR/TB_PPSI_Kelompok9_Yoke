<?php

namespace App\Http\Controllers\Klaim;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class SamKlaimController extends Controller implements HasMiddleware
{
    /** Klaim yang menunggu lebih dari batas ini diberi penanda di antrean. */
    private const BATAS_HARI_MENUNGGU = 3;

    /** Nomor BAK contoh = nomor terakhir + id klaim, sampai tabel berita_acara_kerusakan disambungkan. */
    private const ID_BAK_TERAKHIR = 9;

    /** @var array{nama: string, jabatan: string} */
    private const PENGESAH = ['nama' => 'Farhan Fitrahadi', 'jabatan' => 'Sales Area Manager Sumbar & Riau'];

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Klaim belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function antrean(): View
    {
        $hariIni = CarbonImmutable::now()->startOfDay();
        $antrean = collect($this->contohAntrean())
            ->map(fn (array $klaim) => $klaim + ['lama_menunggu' => (int) $klaim['tanggal_pengajuan']->diffInDays($hariIni)])
            ->sortByDesc('lama_menunggu')->values();
        // Tanggal pengesahan Berita Acara Kerusakan oleh SAM ini (data contoh).
        $tanggalBakDisahkan = collect(['2026-11-17', '2026-11-06', '2026-10-21'])->map(fn (string $t) => CarbonImmutable::parse($t));

        return view('pages.klaim.sam.antrean', [
            'antrean' => $antrean,
            'batasHariMenunggu' => self::BATAS_HARI_MENUNGGU,
            'stats' => [
                'menunggu_kunjungan' => $antrean->count(),
                'kunjungan_hari_ini' => $antrean->filter(fn (array $klaim) => $klaim['jadwal_kunjungan']?->isSameDay($hariIni))->count(),
                'bak_bulan_ini' => $tanggalBakDisahkan->filter(fn (CarbonImmutable $t) => $t->isSameMonth($hariIni))->count(),
            ],
        ]);
    }

    public function verifikasi(int $klaim): View
    {
        $data = collect($this->contohAntrean())->firstWhere('id', $klaim);
        abort_unless($data, 404);

        return view('pages.klaim.sam.verifikasi', ['klaim' => $data]);
    }

    public function pengesahan(int $klaim): View
    {
        $data = collect($this->contohAntrean())->firstWhere('id', $klaim);
        abort_unless($data, 404);

        // Sementara: hasil verifikasi lapangan belum disimpan, jadi jumlah terverifikasi memakai data contoh.
        $baris = collect($data['baris'])->map(fn (array $b) => $b + [
            'penanggung' => DistributorKlaimController::JENIS_KERUSAKAN[$b['jenis']]['penanggung'],
        ]);

        return view('pages.klaim.sam.pengesahan', [
            'klaim' => $data,
            'bak' => [
                'kode' => 'BAK-'.str_pad((string) (self::ID_BAK_TERAKHIR + $data['id']), 5, '0', STR_PAD_LEFT),
                'tanggal_verifikasi' => CarbonImmutable::now(),
                'baris' => $baris,
                'per_penanggung' => $baris->groupBy('penanggung')->map(fn ($kelompok) => $kelompok->sum('terverifikasi')),
            ],
            'pengesah' => self::PENGESAH,
        ]);
    }

    /**
     * Klaim dari seluruh distributor di wilayah kerja SAM yang menunggu pemeriksaan fisik (data contoh).
     *
     * @return list<array{id: int, kode: string, distributor: string, kota: string, nomor_kontainer: string, tanggal_tiba: CarbonImmutable, jumlah_karton: int, tanggal_pengajuan: CarbonImmutable, jadwal_kunjungan: ?CarbonImmutable, baris: list<array{produk: string, jenis: string, karton: int, terverifikasi: int, foto: list<string>}>}>
     */
    private function contohAntrean(): array
    {
        $rusak = fn (string $produk, string $jenis, int $karton, array $foto, ?int $terverifikasi = null) => ['produk' => $produk, 'jenis' => $jenis, 'karton' => $karton, 'terverifikasi' => $terverifikasi ?? $karton, 'foto' => $foto];
        $baris = [
            [16, 'PT Tirta Niaga Mandiri', 'Pekanbaru', 'BMOU 2871450', '2026-11-05', '2026-11-12', '2026-11-20', [
                $rusak('YFI Isotonik Refresh 330ml', 'Basah', 70, ['KLM16-basah-01.jpg', 'KLM16-basah-02.jpg']),
                $rusak('YFI Teh Melati 500ml', 'Penyok', 40, ['KLM16-penyok-01.jpg']),
            ]],
            [18, 'PT Minang Niaga Jaya', 'Padang', 'MRKU 4821937', '2026-11-16', '2026-11-19', '2026-11-20', [
                $rusak('YFI Teh Melati 500ml', 'Penyok', 40, ['KLM18-penyok-01.jpg', 'KLM18-penyok-02.jpg'], 38),
                $rusak('YFI Jus Apel 330ml', 'Bocor', 24, ['KLM18-bocor-01.jpg']),
            ]],
            [19, 'PT Tirta Niaga Mandiri', 'Pekanbaru', 'SEGU 5172093', '2026-11-10', '2026-11-16', null, [
                $rusak('FAN Sparkling Water 325ml', 'Bocor', 36, ['KLM19-bocor-01.jpg', 'KLM19-bocor-02.jpg']),
            ]],
            [20, 'PT Tirta Niaga Mandiri', 'Pekanbaru', 'TEMU 6038214', '2026-11-14', '2026-11-20', null, [
                $rusak('YFI Jus Apel 330ml', 'Penyok', 30, ['KLM20-penyok-01.jpg']),
                $rusak('YFI Isotonik Refresh 330ml', 'Kurang Jumlah', 22, ['KLM20-palet-01.jpg']),
            ]],
        ];

        return array_map(fn (array $b) => [
            'id' => $b[0], 'kode' => 'KLM-'.str_pad((string) $b[0], 5, '0', STR_PAD_LEFT),
            'distributor' => $b[1], 'kota' => $b[2], 'nomor_kontainer' => $b[3],
            'tanggal_tiba' => CarbonImmutable::parse($b[4]),
            'jumlah_karton' => array_sum(array_column($b[7], 'karton')),
            'tanggal_pengajuan' => CarbonImmutable::parse($b[5]),
            'jadwal_kunjungan' => $b[6] ? CarbonImmutable::parse($b[6]) : null,
            'baris' => $b[7],
        ], $baris);
    }
}
