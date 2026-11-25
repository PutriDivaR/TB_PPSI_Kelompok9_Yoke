<?php

namespace App\Http\Controllers\Klaim;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcaraKerusakan;
use App\Models\DetailKlaim;
use App\Models\Klaim;
use App\Models\Pengguna;
use App\Services\KlaimService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SamKlaimController extends Controller implements HasMiddleware
{
    /** Klaim yang menunggu lebih dari batas ini diberi penanda di antrean. */
    private const BATAS_HARI_MENUNGGU = 3;

    /** Batas panjang data URL tanda tangan (sekitar 1,5 MB gambar PNG). */
    private const MAKS_TANDA_TANGAN = 2_000_000;

    private const RELASI_KLAIM = [
        'penerimaan.pengiriman.po.forecast.distributor.wilayah',
        'detailKlaimRecords.detailPenerimaan.detailPengiriman.detailPo.produk',
        'detailKlaimRecords.fotoBuktiRecords',
        'beritaAcaraKerusakanRecord',
    ];

    public function __construct(private KlaimService $klaimService) {}

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Klaim belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function antrean(): View
    {
        $hariIni = now()->startOfDay();
        // shortcut: pengguna belum terhubung ke wilayah kerja, jadi SAM melihat klaim semua wilayah; tambahkan relasi SAM-wilayah bila ERD diperluas.
        $antrean = Klaim::query()->where('status', KlaimService::STATUS_MENUNGGU_SAM)->with(self::RELASI_KLAIM)->get()
            ->map(fn (Klaim $klaim) => $this->identitas($klaim) + [
                'jumlah_karton' => (int) $klaim->detailKlaimRecords->sum('jumlah'),
                'tanggal_pengajuan' => $klaim->tanggal_pengajuan,
                'jadwal_kunjungan' => $klaim->jadwal_kunjungan,
                'catatan_logistik' => $klaim->catatan_pengembalian,
                'lama_menunggu' => (int) $klaim->tanggal_pengajuan->copy()->startOfDay()->diffInDays($hariIni),
            ])->sortByDesc('lama_menunggu')->values();

        return view('pages.klaim.sam.antrean', [
            'antrean' => $antrean,
            'batasHariMenunggu' => self::BATAS_HARI_MENUNGGU,
            'stats' => [
                'menunggu_kunjungan' => $antrean->count(),
                'kunjungan_hari_ini' => $antrean->filter(fn (array $klaim) => $klaim['jadwal_kunjungan']?->isSameDay($hariIni))->count(),
                'bak_bulan_ini' => BeritaAcaraKerusakan::query()->where('status', KlaimService::BAK_DISAHKAN)
                    ->whereBetween('tanggal_pengesahan', [$hariIni->copy()->startOfMonth(), $hariIni->copy()->endOfMonth()])->count(),
            ],
        ]);
    }

    public function jadwalkan(Request $request, Klaim $klaim): RedirectResponse
    {
        $nilai = $request->validate(['jadwal_kunjungan' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']],
            ['jadwal_kunjungan.after_or_equal' => 'Jadwal kunjungan tidak boleh sebelum hari ini.']);
        $this->klaimService->jadwalkanKunjungan($klaim, $nilai['jadwal_kunjungan'], $this->samAktif());

        return redirect()->route('klaim.sam.antrean')->with('success', 'Jadwal kunjungan '.KlaimService::kodeKlaim($klaim).' disimpan.');
    }

    public function verifikasi(Klaim $klaim): View|RedirectResponse
    {
        if ($klaim->status !== KlaimService::STATUS_MENUNGGU_SAM) {
            return redirect()->route('klaim.sam.antrean')->with('error', KlaimService::kodeKlaim($klaim).' tidak sedang menunggu verifikasi SAM.');
        }
        $klaim->load(self::RELASI_KLAIM);

        return view('pages.klaim.sam.verifikasi', [
            'klaim' => $this->identitas($klaim) + [
                'tanggal_tiba' => $klaim->penerimaan->tanggal_terima,
                'catatan_logistik' => $klaim->catatan_pengembalian,
                'baris' => $klaim->detailKlaimRecords->map(fn (DetailKlaim $detail) => $this->barisKlaim($detail) + [
                    'hasil' => match ($detail->status_verifikasi) {
                        KlaimService::VERIFIKASI_SESUAI => 'sesuai',
                        KlaimService::VERIFIKASI_TIDAK_SESUAI => 'tidak_sesuai',
                        default => '',
                    },
                    'jumlah_terverifikasi' => $detail->jumlah_terverifikasi ?? $detail->jumlah,
                    'catatan' => $detail->catatan_verifikasi ?? '',
                    'foto_distributor' => $detail->fotoBuktiRecords->where('sumber', '!==', 'SAM')->map(fn ($foto) => route('klaim.foto', $foto))->values()->all(),
                    'jumlah_foto_sam' => $detail->fotoBuktiRecords->where('sumber', 'SAM')->count(),
                ])->values()->all(),
            ],
        ]);
    }

    public function simpanVerifikasi(Request $request, Klaim $klaim): RedirectResponse
    {
        $nilai = $request->validate([
            'hasil' => ['required', 'array', 'min:1'],
            'hasil.*.status_verifikasi' => ['required', Rule::in([KlaimService::VERIFIKASI_SESUAI, KlaimService::VERIFIKASI_TIDAK_SESUAI])],
            'hasil.*.jumlah_terverifikasi' => ['nullable', 'integer', 'min:0'],
            'hasil.*.catatan' => ['nullable', 'string', 'max:255'],
            'foto_lokasi' => ['sometimes', 'array'],
            'foto_lokasi.*' => ['array', 'max:8'],
            'foto_lokasi.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'hasil.*.status_verifikasi.required' => 'Tandai Sesuai atau Tidak Sesuai pada setiap baris.',
            'foto_lokasi.*.*.image' => 'Foto dokumentasi harus berupa gambar.',
            'foto_lokasi.*.*.max' => 'Ukuran foto dokumentasi maksimal 5 MB.',
        ]);
        $fotoLokasi = collect($request->file('foto_lokasi', []))->map(fn ($berkas) => (array) $berkas)->all();
        $this->klaimService->simpanVerifikasi($klaim, $nilai['hasil'], $fotoLokasi, $this->samAktif());

        return redirect()->route('klaim.sam.pengesahan', $klaim)->with('success', 'Hasil verifikasi fisik tersimpan. Periksa berita acara lalu tanda tangani.');
    }

    public function kembalikan(Request $request, Klaim $klaim): RedirectResponse
    {
        $nilai = $request->validate(['catatan' => ['required', 'string', 'max:1000']],
            ['catatan.required' => 'Tuliskan catatan perbaikan untuk distributor.']);
        $this->klaimService->kembalikanKeDistributor($klaim, $nilai['catatan'], $this->samAktif());

        return redirect()->route('klaim.sam.antrean')->with('success', KlaimService::kodeKlaim($klaim).' dikembalikan ke distributor untuk diperbaiki.');
    }

    public function pengesahan(Klaim $klaim): View|RedirectResponse
    {
        if ($klaim->status !== KlaimService::STATUS_MENUNGGU_SAM) {
            return redirect()->route('klaim.sam.antrean')->with('error', KlaimService::kodeKlaim($klaim).' tidak sedang menunggu pengesahan.');
        }
        $klaim->load(self::RELASI_KLAIM);
        if ($klaim->detailKlaimRecords->contains('status_verifikasi', KlaimService::VERIFIKASI_MENUNGGU)) {
            return redirect()->route('klaim.sam.verifikasi', $klaim)->with('error', 'Selesaikan verifikasi fisik semua baris terlebih dahulu.');
        }
        $baris = $klaim->detailKlaimRecords->map(fn (DetailKlaim $detail) => $this->barisKlaim($detail) + [
            'terverifikasi' => (int) $detail->jumlah_terverifikasi,
            'penanggung' => $detail->penanggung_jawab,
        ]);
        $sam = $this->samAktif();

        return view('pages.klaim.sam.pengesahan', [
            'klaim' => $this->identitas($klaim) + ['tanggal_tiba' => $klaim->penerimaan->tanggal_terima],
            'bak' => [
                'kode' => $klaim->beritaAcaraKerusakanRecord?->nomor_bak,
                'tanggal_verifikasi' => now(),
                'baris' => $baris,
                'per_penanggung' => $baris->groupBy('penanggung')->map(fn ($kelompok) => $kelompok->sum('terverifikasi')),
            ],
            'pengesah' => ['nama' => $sam->nama, 'jabatan' => 'Sales Area Manager'],
        ]);
    }

    public function sahkan(Request $request, Klaim $klaim): RedirectResponse
    {
        $nilai = $request->validate(['tanda_tangan' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:'.self::MAKS_TANDA_TANGAN]],
            ['tanda_tangan.required' => 'Bubuhkan tanda tangan sebelum mengesahkan.', 'tanda_tangan.starts_with' => 'Format tanda tangan tidak dikenali.']);
        $png = base64_decode(substr($nilai['tanda_tangan'], strlen('data:image/png;base64,')), true);
        if ($png === false || (@getimagesizefromstring($png)['mime'] ?? null) !== 'image/png') {
            throw ValidationException::withMessages(['tanda_tangan' => 'Gambar tanda tangan tidak valid.']);
        }
        $bak = $this->klaimService->sahkan($klaim, $png, $this->samAktif());

        return redirect()->route('klaim.sam.antrean')->with('success', $bak->nomor_bak.' disahkan dan diteruskan ke Tim Logistik Pusat.');
    }

    /** @return array<string, mixed> */
    private function identitas(Klaim $klaim): array
    {
        $distributor = $klaim->penerimaan->pengiriman->po->forecast->distributor;

        return [
            'id' => $klaim->id_klaim,
            'kode' => KlaimService::kodeKlaim($klaim),
            'distributor' => $distributor->nama,
            'kota' => $distributor->wilayah?->nama_wilayah,
            'nomor_kontainer' => $klaim->penerimaan->pengiriman->nomor_kontainer,
        ];
    }

    /** @return array<string, mixed> */
    private function barisKlaim(DetailKlaim $detail): array
    {
        return [
            'id' => $detail->id_detail_klaim,
            'produk' => $detail->detailPenerimaan->detailPengiriman->detailPo->produk->nama_produk,
            'jenis' => $detail->jenis_kerusakan,
            'karton' => $detail->jumlah,
        ];
    }

    private function samAktif(): Pengguna
    {
        $sam = Pengguna::query()->where('status_aktif', true)
            ->whereHas('peran', fn ($query) => $query->where('nama_peran', 'SAM'))->orderBy('id_pengguna')->first();
        abort_unless($sam, 422, 'Belum ada pengguna aktif berperan SAM.');

        return $sam;
    }
}
