<?php

namespace App\Http\Controllers\Klaim;

use App\Http\Controllers\Controller;
use App\Models\DetailKlaim;
use App\Models\DetailPenerimaan;
use App\Models\Distributor;
use App\Models\Klaim;
use App\Models\PenerimaanBarang;
use App\Models\Pengguna;
use App\Models\TahunFiskal;
use App\Services\KlaimService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

class DistributorKlaimController extends Controller implements HasMiddleware
{
    /**
     * Label dan warna lencana per status klaim.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const LENCANA_STATUS = [
        KlaimService::STATUS_MENUNGGU_SAM => ['Menunggu verifikasi SAM', 'bg-amber-50 text-amber-700'],
        KlaimService::STATUS_DI_LOGISTIK => ['Diproses Logistik', 'bg-blue-50 text-blue-700'],
        KlaimService::STATUS_SELESAI => ['Selesai', 'bg-emerald-50 text-emerald-700'],
        KlaimService::STATUS_DIKEMBALIKAN => ['Dikembalikan', 'bg-red-50 text-red-700'],
    ];

    /** @var list<string> */
    private const TAHAP_KLAIM = [
        'Klaim Diajukan',
        'Diteruskan ke Sales Area Manager',
        'Verifikasi Fisik di Gudang',
        'Pengesahan Berita Acara Kerusakan',
        'Penyelesaian oleh Logistik Pusat',
    ];

    private const RELASI_KLAIM = [
        'penerimaan.pengiriman.po',
        'detailKlaimRecords.detailPenerimaan.detailPengiriman.detailPo.produk',
        'detailKlaimRecords.fotoBuktiRecords',
        'beritaAcaraKerusakanRecord.pengesah',
    ];

    public function __construct(private KlaimService $klaimService) {}

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Klaim belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function beranda(Request $request): View
    {
        $distributor = $this->distributorAktif($request);
        if (! $distributor) {
            return view('pages.klaim.distributor.beranda', ['distributor' => null]);
        }
        $hariIni = now()->startOfDay();
        $riwayat = Klaim::query()->milikDistributor($distributor->id_distributor)->with(self::RELASI_KLAIM)
            ->orderByDesc('tanggal_pengajuan')->get()->map(fn (Klaim $klaim) => $this->ringkasKlaim($klaim));
        $tahunFiskal = TahunFiskal::query()->whereDate('tanggal_mulai', '<=', $hariIni)->whereDate('tanggal_selesai', '>=', $hariIni)->first();
        $kedatanganTerakhir = $this->penerimaanDistributor($distributor)->doesntHave('klaimRecordAsPenerimaan')
            ->with('pengiriman')->orderByDesc('tanggal_terima')->first();
        $batasPenyortiran = $kedatanganTerakhir?->tanggal_terima->copy()->startOfDay()->addDays(KlaimService::HARI_PENYORTIRAN);

        return view('pages.klaim.distributor.beranda', [
            'distributor' => $distributor,
            'riwayat' => $riwayat,
            'kedatanganTerakhir' => $kedatanganTerakhir,
            'batasPenyortiran' => $batasPenyortiran,
            'tahunFiskal' => $tahunFiskal,
            'stats' => [
                'menunggu_verifikasi' => $riwayat->where('status', KlaimService::STATUS_MENUNGGU_SAM)->count(),
                'sisa_hari_sortir' => $batasPenyortiran ? (int) $hariIni->diffInDays($batasPenyortiran, false) : null,
                'selesai_bulan_ini' => $riwayat->where('status', KlaimService::STATUS_SELESAI)
                    ->filter(fn (array $klaim) => $klaim['tanggal_selesai']?->isSameMonth($hariIni))->count(),
                'karton_tahun_fiskal' => $tahunFiskal ? $riwayat
                    ->filter(fn (array $klaim) => $klaim['tanggal_pengajuan']->between($tahunFiskal->tanggal_mulai, $tahunFiskal->tanggal_selesai->copy()->endOfDay()))
                    ->sum('jumlah_karton') : 0,
            ],
        ]);
    }

    public function ajukan(Request $request): View|RedirectResponse
    {
        $distributor = $this->distributorAktif($request);
        if (! $distributor) {
            return redirect()->route('klaim.distributor.beranda');
        }
        // Kedatangan yang bisa diklaim: belum punya klaim, atau klaimnya dikembalikan untuk diperbaiki.
        $kedatangan = $this->penerimaanDistributor($distributor)
            ->where(fn ($query) => $query->doesntHave('klaimRecordAsPenerimaan')
                ->orWhereHas('klaimRecordAsPenerimaan', fn ($klaim) => $klaim->where('status', KlaimService::STATUS_DIKEMBALIKAN)))
            ->with(['pengiriman.po', 'detailPenerimaanRecordsAsPenerimaan.detailPengiriman.detailPo.produk', 'klaimRecordAsPenerimaan.detailKlaimRecords'])
            ->orderByDesc('tanggal_terima')->get()
            ->map(fn (PenerimaanBarang $penerimaan) => $this->ringkasKedatangan($penerimaan));

        return view('pages.klaim.distributor.ajukan', [
            'distributor' => $distributor,
            'kedatangan' => $kedatangan,
            'pilihAwal' => (string) $request->integer('penerimaan') ?: '',
            'jenisKerusakan' => KlaimService::JENIS_KERUSAKAN,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $distributor = $this->distributorAktif($request);
        abort_unless($distributor, 422, 'Belum ada distributor aktif.');
        $nilai = $request->validate([
            'id_penerimaan' => ['required', 'integer', 'exists:penerimaan_barang,id_penerimaan'],
            'baris' => ['required', 'array', 'min:1'],
            'baris.*.id_detail_penerimaan' => ['required', 'integer'],
            'baris.*.jenis_kerusakan' => ['required', Rule::in(array_keys(KlaimService::JENIS_KERUSAKAN))],
            'baris.*.jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
            'baris.*.foto' => ['sometimes', 'array', 'max:8'],
            'baris.*.foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'baris.required' => 'Tambahkan minimal satu baris kerusakan.',
            'baris.*.id_detail_penerimaan.required' => 'Pilih produk pada setiap baris kerusakan.',
            'baris.*.jenis_kerusakan.required' => 'Pilih jenis kerusakan pada setiap baris.',
            'baris.*.foto.*.image' => 'Foto bukti harus berupa gambar.',
            'baris.*.foto.*.max' => 'Ukuran foto bukti maksimal 5 MB.',
        ]);
        $baris = collect($nilai['baris'])->map(fn (array $item, int|string $kunci) => [
            'id_detail_penerimaan' => (int) $item['id_detail_penerimaan'],
            'jenis_kerusakan' => $item['jenis_kerusakan'],
            'jumlah' => (int) $item['jumlah'],
            'foto' => $request->file("baris.{$kunci}.foto", []),
        ])->values()->all();
        $klaim = $this->klaimService->ajukan((int) $nilai['id_penerimaan'], $distributor->id_distributor, $baris, $distributor->penggunaRecord);

        return redirect()->route('klaim.distributor.detail', $klaim)
            ->with('success', 'Klaim '.KlaimService::kodeKlaim($klaim).' terkirim dan menunggu verifikasi Sales Area Manager.');
    }

    public function detail(Request $request, Klaim $klaim): View
    {
        $distributor = $this->distributorAktif($request);
        abort_unless($distributor && Klaim::query()->whereKey($klaim->id_klaim)->milikDistributor($distributor->id_distributor)->exists(), 404);
        $klaim->load(self::RELASI_KLAIM);
        $bak = $klaim->beritaAcaraKerusakanRecord;
        $bakSah = $bak?->status === KlaimService::BAK_DISAHKAN;
        [$jumlahLewat, $keteranganBerjalan] = match ($klaim->status) {
            KlaimService::STATUS_SELESAI => [5, null],
            KlaimService::STATUS_DI_LOGISTIK => [4, 'Sedang berjalan'],
            KlaimService::STATUS_DIKEMBALIKAN => [0, 'Dikembalikan untuk diperbaiki'],
            default => [2, 'Sedang berjalan'],
        };
        $tanggalTahap = [$klaim->tanggal_pengajuan, $klaim->tanggal_pengajuan, $bak?->tanggal_pengesahan, $bak?->tanggal_pengesahan, $klaim->tanggal_penyelesaian];
        $tahap = collect(self::TAHAP_KLAIM)->map(fn (string $nama, int $i) => [
            'nama' => $nama,
            'status' => $i < $jumlahLewat ? 'lewat' : ($i === $jumlahLewat ? 'berjalan' : 'belum'),
            'tanggal' => $i < $jumlahLewat ? $tanggalTahap[$i] : null,
            'keterangan' => $keteranganBerjalan,
        ]);

        return view('pages.klaim.distributor.detail', [
            'klaim' => $this->ringkasKlaim($klaim) + [
                'nomor_po' => $klaim->penerimaan->pengiriman->po->nomor_po,
                'catatan_pengembalian' => $klaim->catatan_pengembalian,
                'bentuk_penyelesaian' => $klaim->bentuk_penyelesaian,
                'catatan_penyelesaian' => $klaim->catatan_penyelesaian,
                'id_penerimaan' => $klaim->id_penerimaan,
                'baris' => $klaim->detailKlaimRecords->map(fn (DetailKlaim $detail) => [
                    'kode' => $detail->detailPenerimaan->detailPengiriman->detailPo->produk->kode_sku,
                    'nama' => $detail->detailPenerimaan->detailPengiriman->detailPo->produk->nama_produk,
                    'jenis' => $detail->jenis_kerusakan,
                    'karton' => $detail->jumlah,
                    'terverifikasi' => $detail->jumlah_terverifikasi,
                    'catatan_verifikasi' => $detail->catatan_verifikasi,
                    'foto' => $detail->fotoBuktiRecords->map(fn ($foto) => ['url' => route('klaim.foto', $foto), 'sumber' => $foto->sumber ?? 'Distributor'])->all(),
                ])->all(),
                'bak' => $bakSah ? [
                    'kode' => $bak->nomor_bak,
                    'tanggal' => $bak->tanggal_pengesahan,
                    'pengesah' => $bak->pengesah->nama,
                    'url' => route('klaim.bak', $klaim),
                ] : null,
            ],
            'tahap' => $tahap,
        ]);
    }

    /** @return array<string, mixed> */
    private function ringkasKlaim(Klaim $klaim): array
    {
        $details = $klaim->detailKlaimRecords;

        return [
            'id' => $klaim->id_klaim,
            'kode' => KlaimService::kodeKlaim($klaim),
            'nomor_kontainer' => $klaim->penerimaan->pengiriman->nomor_kontainer,
            'tanggal_tiba' => $klaim->penerimaan->tanggal_terima,
            'tanggal_pengajuan' => $klaim->tanggal_pengajuan,
            'tanggal_selesai' => $klaim->tanggal_penyelesaian,
            'jumlah_karton' => (int) $details->sum('jumlah'),
            'jenis' => $details->pluck('jenis_kerusakan')->unique()->implode(', '),
            'penanggung' => $details->pluck('penanggung_jawab')->unique()->implode(' & '),
            'status' => $klaim->status,
            'lencana' => self::LENCANA_STATUS[$klaim->status] ?? [$klaim->status, 'bg-slate-100 text-slate-700'],
        ];
    }

    /** @return array<string, mixed> */
    private function ringkasKedatangan(PenerimaanBarang $penerimaan): array
    {
        $baris = $penerimaan->detailPenerimaanRecordsAsPenerimaan;
        $dikembalikan = $penerimaan->klaimRecordAsPenerimaan;

        return [
            'id' => $penerimaan->id_penerimaan,
            'nomor_po' => $penerimaan->pengiriman->po->nomor_po,
            'nomor_kontainer' => $penerimaan->pengiriman->nomor_kontainer,
            'tanggal_tiba' => $penerimaan->tanggal_terima->locale('id')->translatedFormat('d M Y'),
            'jumlah_invoice' => (int) $baris->sum(fn (DetailPenerimaan $b) => $b->detailPengiriman->jumlah_dikirim),
            'jumlah_diterima' => (int) $baris->sum('jumlah_diterima'),
            'jumlah_baik' => (int) $baris->sum('jumlah_baik'),
            'baris' => $baris->map(fn (DetailPenerimaan $b) => [
                'id' => $b->id_detail_penerimaan,
                'kode' => $b->detailPengiriman->detailPo->produk->kode_sku,
                'nama' => $b->detailPengiriman->detailPo->produk->nama_produk,
                'maks' => KlaimService::maksimumKlaim($b),
            ])->values()->all(),
            'dikembalikan' => $dikembalikan ? [
                'kode' => KlaimService::kodeKlaim($dikembalikan),
                'catatan' => $dikembalikan->catatan_pengembalian,
                'baris' => $dikembalikan->detailKlaimRecords->map(fn (DetailKlaim $d) => [
                    'id_detail_penerimaan' => $d->id_detail_penerimaan, 'jenis' => $d->jenis_kerusakan, 'karton' => $d->jumlah,
                ])->values()->all(),
            ] : null,
        ];
    }

    /** @return Builder<PenerimaanBarang> */
    private function penerimaanDistributor(Distributor $distributor): Builder
    {
        return PenerimaanBarang::query()->whereHas('pengiriman.po.forecast', fn ($forecast) => $forecast->where('id_distributor', $distributor->id_distributor));
    }

    private function distributorAktif(Request $request): ?Distributor
    {
        $pengguna = $request->user();
        if (! $pengguna instanceof Pengguna || ! $pengguna->distributor?->status_aktif) {
            return null;
        }

        return $pengguna->distributor->loadMissing('wilayah', 'penggunaRecord');
    }
}
