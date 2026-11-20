<?php

namespace App\Http\Controllers\Klaim;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class DistributorKlaimController extends Controller implements HasMiddleware
{
    /** Distributor menyortir barang sekitar satu minggu setelah barang tiba di gudang. */
    private const HARI_PENYORTIRAN = 7;

    /**
     * Jenis klaim beserta pihak yang menanggung (aturan pasti dari wawancara SAM).
     *
     * @var array<string, string>
     */
    private const PENANGGUNG_KLAIM = [
        'Kerusakan Barang Dalam Perjalanan' => 'Perusahaan',
        'Barang Kurang dari Invoice' => 'Perusahaan',
        'Kerusakan Penyimpanan Gudang' => 'Distributor',
        'Retur Kedaluwarsa dari Toko' => 'Distributor',
    ];

    /**
     * Jenis kerusakan beserta penanggung dan alasannya.
     *
     * Daftar jenis ini masih sementara dan menunggu konfirmasi dari perusahaan.
     * shortcut: Penyok/Bocor/Basah dianggap kerusakan Barang Dalam Perjalanan karena jenisnya saja
     * tidak membedakan kerusakan penyimpanan gudang; perlu penyebab kerusakan jika aturan ini dipastikan.
     *
     * @var array<string, array{penanggung: string, alasan: string}>
     */
    public const JENIS_KERUSAKAN = [
        'Penyok' => ['penanggung' => 'Perusahaan', 'alasan' => 'Kerusakan fisik yang ditemukan saat penyortiran kedatangan dianggap terjadi selama Barang Dalam Perjalanan, dan dipastikan SAM saat pemeriksaan fisik.'],
        'Bocor' => ['penanggung' => 'Perusahaan', 'alasan' => 'Kerusakan fisik yang ditemukan saat penyortiran kedatangan dianggap terjadi selama Barang Dalam Perjalanan, dan dipastikan SAM saat pemeriksaan fisik.'],
        'Basah' => ['penanggung' => 'Perusahaan', 'alasan' => 'Kerusakan fisik yang ditemukan saat penyortiran kedatangan dianggap terjadi selama Barang Dalam Perjalanan, dan dipastikan SAM saat pemeriksaan fisik.'],
        'Kurang Jumlah' => ['penanggung' => 'Perusahaan', 'alasan' => 'Barang yang kurang terhadap invoice menjadi tanggungan perusahaan.'],
        'Kedaluwarsa' => ['penanggung' => 'Distributor', 'alasan' => 'Retur kedaluwarsa menjadi tanggungan distributor.'],
    ];

    /**
     * Status klaim belum baku; label dan warna lencana masih sementara.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const LENCANA_STATUS = [
        'Menunggu Verifikasi SAM' => ['Menunggu verifikasi SAM', 'bg-amber-50 text-amber-700'],
        'Diteruskan ke Logistik' => ['Diproses Logistik', 'bg-blue-50 text-blue-700'],
        'Selesai' => ['Selesai', 'bg-emerald-50 text-emerald-700'],
        'Dikembalikan' => ['Dikembalikan', 'bg-red-50 text-red-700'],
    ];

    /** @var list<string> */
    private const TAHAP_KLAIM = [
        'Klaim Diajukan',
        'Diteruskan ke Sales Area Manager',
        'Verifikasi Fisik di Gudang',
        'Pengesahan Berita Acara Kerusakan',
        'Penyelesaian oleh Logistik Pusat',
    ];

    /** @var array<string, string> */
    private const PRODUK = [
        'YFI-TM-500' => 'YFI Teh Melati 500ml',
        'FAN-SPK-325' => 'FAN Sparkling Water 325ml',
        'YFI-JA-330' => 'YFI Jus Apel 330ml',
        'YFI-ISO-330' => 'YFI Isotonik Refresh 330ml',
    ];

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Klaim belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function beranda(): View
    {
        $hariIni = CarbonImmutable::now()->startOfDay();
        // shortcut: tahun fiskal ditulis langsung, ganti dengan TahunFiskal aktif saat modul disambungkan ke database.
        $tahunFiskal = ['label' => 'FY-2026', 'mulai' => CarbonImmutable::parse('2026-01-01'), 'selesai' => CarbonImmutable::parse('2026-12-31')];
        $riwayat = collect($this->contohKlaim());

        $kedatanganTerakhir = ['nomor_kontainer' => 'SEGU 2240718', 'tanggal_tiba' => CarbonImmutable::parse('2026-11-18')];
        $batasPenyortiran = $kedatanganTerakhir['tanggal_tiba']->addDays(self::HARI_PENYORTIRAN);

        return view('pages.klaim.distributor.beranda', [
            'distributor' => ['nama' => 'PT Minang Niaga Jaya', 'wilayah' => 'Padang, Sumatera Barat'],
            'riwayat' => $riwayat,
            'kedatanganTerakhir' => $kedatanganTerakhir,
            'batasPenyortiran' => $batasPenyortiran,
            'tahunFiskal' => $tahunFiskal,
            'stats' => [
                'menunggu_verifikasi' => $riwayat->where('status', 'Menunggu Verifikasi SAM')->count(),
                'sisa_hari_sortir' => (int) $hariIni->diffInDays($batasPenyortiran, false),
                'selesai_bulan_ini' => $riwayat->where('status', 'Selesai')
                    ->filter(fn (array $klaim) => $klaim['tanggal_selesai']?->isSameMonth($hariIni))->count(),
                'karton_tahun_fiskal' => $riwayat
                    ->filter(fn (array $klaim) => $klaim['tanggal_pengajuan']->between($tahunFiskal['mulai'], $tahunFiskal['selesai']))
                    ->sum('jumlah_karton'),
            ],
        ]);
    }

    public function ajukan(): View
    {
        // Sementara: kedatangan yang sudah punya klaim tetap bisa dipilih, karena aturan satu klaim per kedatangan belum pasti.
        $kedatangan = collect([
            [31, 'PO-YFI-2026-0089', 'SEGU 2240718', '2026-11-18', 2000, 1976],
            [30, 'PO-YFI-2026-0089', 'MRKU 4821937', '2026-11-16', 2000, 2000],
            [28, 'PO-YFI-2026-0081', 'TCNU 7702154', '2026-11-09', 2000, 1960],
            [27, 'PO-YFI-2026-0081', 'MSKU 5530192', '2026-11-07', 2000, 2000],
        ])->map(fn (array $k) => [
            'id' => $k[0], 'nomor_po' => $k[1], 'nomor_kontainer' => $k[2],
            'tanggal_tiba' => CarbonImmutable::parse($k[3])->locale('id')->translatedFormat('d M Y'),
            'jumlah_invoice' => $k[4], 'jumlah_diterima' => $k[5], 'selisih' => $k[5] - $k[4],
        ]);

        return view('pages.klaim.distributor.ajukan', [
            'distributor' => ['nama' => 'PT Minang Niaga Jaya', 'wilayah' => 'Padang, Sumatera Barat'],
            'kedatangan' => $kedatangan,
            'produk' => collect(self::PRODUK)->map(fn (string $nama, string $kode) => ['kode' => $kode, 'nama' => $nama])->values(),
            'jenisKerusakan' => self::JENIS_KERUSAKAN,
        ]);
    }

    public function detail(int $klaim): View
    {
        $data = collect($this->contohKlaim())->firstWhere('id', $klaim);
        $rincian = $this->contohDetail()[$klaim] ?? null;
        abort_unless($data && $rincian, 404);

        $jumlahTahapLewat = count($rincian['tanggal_tahap']);
        $tahap = collect(self::TAHAP_KLAIM)->map(fn (string $nama, int $i) => [
            'nama' => $nama,
            'status' => $i < $jumlahTahapLewat ? 'lewat' : ($i === $jumlahTahapLewat ? 'berjalan' : 'belum'),
            'tanggal' => isset($rincian['tanggal_tahap'][$i]) ? CarbonImmutable::parse($rincian['tanggal_tahap'][$i]) : null,
        ]);

        return view('pages.klaim.distributor.detail', [
            'klaim' => $data + $rincian,
            'tahap' => $tahap,
        ]);
    }

    /**
     * Data contoh sementara sampai tabel klaim disambungkan.
     *
     * @return list<array{id: int, kode: string, nomor_kontainer: string, tanggal_tiba: CarbonImmutable, tanggal_pengajuan: CarbonImmutable, jenis: string, penanggung: string, jumlah_karton: int, status: string, lencana: array{0: string, 1: string}, tanggal_selesai: ?CarbonImmutable, punya_detail: bool}>
     */
    private function contohKlaim(): array
    {
        $baris = [
            [18, 'MRKU 4821937', '2026-11-16', '2026-11-19', 'Kerusakan Barang Dalam Perjalanan', 64, 'Menunggu Verifikasi SAM', null],
            [17, 'TCNU 7702154', '2026-11-09', '2026-11-14', 'Barang Kurang dari Invoice', 40, 'Diteruskan ke Logistik', null],
            [15, 'MSKU 3359820', '2026-10-28', '2026-11-03', 'Kerusakan Barang Dalam Perjalanan', 126, 'Selesai', '2026-11-12'],
            [14, 'TGHU 6614028', '2026-10-20', '2026-10-27', 'Kerusakan Penyimpanan Gudang', 22, 'Dikembalikan', null],
            [12, 'CAIU 5087316', '2026-10-06', '2026-10-13', 'Kerusakan Barang Dalam Perjalanan', 88, 'Selesai', '2026-10-24'],
            [11, 'MRKU 1938405', '2026-09-22', '2026-09-28', 'Barang Kurang dari Invoice', 30, 'Selesai', '2026-10-09'],
        ];

        return array_map(fn (array $b) => [
            'id' => $b[0], 'kode' => 'KLM-'.str_pad((string) $b[0], 5, '0', STR_PAD_LEFT), 'nomor_kontainer' => $b[1],
            'tanggal_tiba' => CarbonImmutable::parse($b[2]), 'tanggal_pengajuan' => CarbonImmutable::parse($b[3]),
            'jenis' => $b[4], 'penanggung' => self::PENANGGUNG_KLAIM[$b[4]], 'jumlah_karton' => $b[5],
            'status' => $b[6], 'lencana' => self::LENCANA_STATUS[$b[6]] ?? [$b[6], 'bg-slate-100 text-slate-700'],
            'tanggal_selesai' => $b[7] ? CarbonImmutable::parse($b[7]) : null,
            'punya_detail' => isset($this->contohDetail()[$b[0]]),
        ], $baris);
    }

    /**
     * Rincian contoh untuk dua klaim dengan posisi tahap berbeda.
     *
     * tanggal_tahap berisi tanggal tiap tahap yang sudah lewat; tahap berikutnya dianggap sedang berjalan.
     *
     * @return array<int, array{nomor_po: string, tanggal_tahap: list<string>, baris: list<array{kode: string, nama: string, jenis: string, karton: int, foto: list<string>}>, bak: ?array{kode: string, tanggal: CarbonImmutable, pengesah: string, jabatan: string}}>
     */
    private function contohDetail(): array
    {
        $baris = fn (string $kode, string $jenis, int $karton, array $foto) => ['kode' => $kode, 'nama' => self::PRODUK[$kode], 'jenis' => $jenis, 'karton' => $karton, 'foto' => $foto];

        return [
            18 => [
                'nomor_po' => 'PO-YFI-2026-0089',
                'tanggal_tahap' => ['2026-11-19', '2026-11-19'],
                'baris' => [
                    $baris('YFI-TM-500', 'Penyok', 40, ['KLM18-penyok-01.jpg', 'KLM18-penyok-02.jpg']),
                    $baris('YFI-JA-330', 'Bocor', 24, ['KLM18-bocor-01.jpg']),
                ],
                'bak' => null,
            ],
            17 => [
                'nomor_po' => 'PO-YFI-2026-0081',
                'tanggal_tahap' => ['2026-11-14', '2026-11-14', '2026-11-17', '2026-11-17'],
                'baris' => [
                    $baris('FAN-SPK-325', 'Kurang Jumlah', 25, ['KLM17-palet-01.jpg', 'KLM17-surat-jalan.jpg']),
                    $baris('YFI-ISO-330', 'Kurang Jumlah', 15, ['KLM17-palet-02.jpg']),
                ],
                'bak' => ['kode' => 'BAK-'.str_pad((string) 9, 5, '0', STR_PAD_LEFT), 'tanggal' => CarbonImmutable::parse('2026-11-17'),
                    'pengesah' => 'Farhan Fitrahadi', 'jabatan' => 'Sales Area Manager Sumbar & Riau'],
            ],
        ];
    }
}
