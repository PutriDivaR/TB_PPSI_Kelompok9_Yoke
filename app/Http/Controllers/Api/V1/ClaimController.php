<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClaimResource;
use App\Models\BeritaAcaraKerusakan;
use App\Models\DetailKlaim;
use App\Models\FotoBukti;
use App\Models\Klaim;
use App\Models\LogAktivitas;
use App\Models\PenerimaanBarang;
use App\Models\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClaimController extends Controller
{
    private const EAGER_LOAD = [
        'penerimaan.pengiriman.po.forecast.distributor',
        'detailKlaimRecords.detailPenerimaan.detailPengiriman.detailPo.produk',
        'detailKlaimRecords.fotoBuktiRecords',
        'beritaAcaraKerusakanRecord.pengesah',
    ];

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();
        $claims = Klaim::query()->with(self::EAGER_LOAD)
            ->when($user->peran->nama_peran === 'Distributor', fn ($query) => $query->whereHas(
                'penerimaan.pengiriman.po.forecast',
                fn ($forecast) => $forecast->where('id_distributor', $user->id_distributor)
            ))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('tanggal_pengajuan')->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return ClaimResource::collection($claims)->response();
    }

    public function show(Request $request, Klaim $claim): JsonResponse
    {
        $this->authorizeClaim($request, $claim);

        return (new ClaimResource($claim->load(self::EAGER_LOAD)))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $values = $request->validate([
            'id_penerimaan' => ['required', 'integer', 'exists:penerimaan_barang,id_penerimaan'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_detail_penerimaan' => ['required', 'integer', 'distinct', 'exists:detail_penerimaan,id_detail_penerimaan'],
            'items.*.jenis_kerusakan' => ['required', 'string', 'max:40'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'items.*.penanggung_jawab' => ['required', 'string', 'max:40'],
            'items.*.photos' => ['sometimes', 'array', 'max:8'],
            'items.*.photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $user = $request->user();
        $receipt = PenerimaanBarang::query()->whereKey($values['id_penerimaan'])
            ->with('pengiriman.po.forecast')->firstOrFail();
        abort_unless((int) $receipt->pengiriman->po->forecast->id_distributor === (int) $user->id_distributor, 403,
            'Penerimaan bukan milik perusahaan Anda.');

        $claim = DB::transaction(function () use ($values, $user, $request) {
            if (Klaim::query()->where('id_penerimaan', $values['id_penerimaan'])->exists()) {
                throw ValidationException::withMessages(['id_penerimaan' => ['Kedatangan ini sudah memiliki klaim.']]);
            }

            $claim = Klaim::query()->create([
                'id_penerimaan' => $values['id_penerimaan'],
                'tanggal_pengajuan' => now(),
                'status' => 'Menunggu Verifikasi SAM',
            ]);
            $receiptLines = DB::table('detail_penerimaan as dpn')
                ->join('detail_pengiriman as dpg', 'dpg.id_detail_pengiriman', '=', 'dpn.id_detail_pengiriman')
                ->where('dpn.id_penerimaan', $values['id_penerimaan'])
                ->select('dpn.*', 'dpg.jumlah_dikirim')
                ->get()->keyBy('id_detail_penerimaan');

            foreach ($values['items'] as $index => $item) {
                $receiptLine = $receiptLines->get($item['id_detail_penerimaan']);
                abort_unless($receiptLine, 422, 'Baris klaim harus berasal dari penerimaan yang dipilih.');
                $maximumClaimable = (int) $receiptLine->jumlah_dikirim - (int) $receiptLine->jumlah_baik;
                if ($item['jumlah'] > $maximumClaimable) {
                    throw ValidationException::withMessages(['items' => ['Jumlah klaim melebihi jumlah barang yang tidak layak jual pada penerimaan.']]);
                }

                $detail = DetailKlaim::query()->create([
                    'id_klaim' => $claim->id_klaim,
                    'id_detail_penerimaan' => $item['id_detail_penerimaan'],
                    'jenis_kerusakan' => $item['jenis_kerusakan'],
                    'jumlah' => $item['jumlah'],
                    'penanggung_jawab' => $item['penanggung_jawab'],
                    'status_verifikasi' => 'Menunggu Verifikasi',
                ]);
                foreach ($request->file("items.{$index}.photos", []) as $photo) {
                    $path = $photo->store('klaim/bukti', 'local');
                    abort_unless($path, 500, 'Berkas bukti klaim gagal disimpan.');
                    FotoBukti::query()->create([
                        'id_detail_klaim' => $detail->id_detail_klaim,
                        'path_berkas' => $path,
                        'tanggal_unggah' => now(),
                    ]);
                }
            }
            $this->recordActivity($user, 'Klaim', 'Ajukan klaim');

            return $claim;
        });

        return (new ClaimResource($claim->load(self::EAGER_LOAD)))->response()->setStatusCode(201);
    }

    public function verify(Request $request, Klaim $claim): JsonResponse
    {
        $values = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_detail_klaim' => ['required', 'integer', 'distinct'],
            'items.*.status_verifikasi' => ['required', 'in:Disetujui,Ditolak'],
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);
        $user = $request->user();

        DB::transaction(function () use ($claim, $values, $user, $request) {
            $locked = Klaim::query()->whereKey($claim->id_klaim)->lockForUpdate()->with('detailKlaimRecords')->firstOrFail();
            if ($locked->status !== 'Menunggu Verifikasi SAM') {
                throw ValidationException::withMessages(['status' => ['Klaim ini tidak menunggu verifikasi SAM.']]);
            }
            $decisions = collect($values['items'])->keyBy('id_detail_klaim');
            $details = $locked->detailKlaimRecords->keyBy('id_detail_klaim');
            if ($decisions->keys()->sort()->values()->all() !== $details->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages(['items' => ['Setiap baris klaim harus diverifikasi.']]);
            }

            foreach ($decisions as $id => $decision) {
                $details[$id]->update(['status_verifikasi' => $decision['status_verifikasi']]);
            }
            if ($decisions->contains(fn (array $decision): bool => $decision['status_verifikasi'] === 'Ditolak')) {
                $locked->update(['status' => 'Dikembalikan']);
            } else {
                $signaturePath = Storage::disk('local')->putFile('klaim/tanda-tangan', $request->file('signature'));
                abort_unless($signaturePath, 500, 'Berkas tanda tangan gagal disimpan.');
                $certificate = BeritaAcaraKerusakan::query()->create([
                    'id_klaim' => $locked->id_klaim,
                    'nomor_bak' => 'TMP-'.(string) Str::ulid(),
                    'id_pengesah' => $user->id_pengguna,
                    'tanggal_pengesahan' => now(),
                    'tanda_tangan_digital' => $signaturePath,
                    'status' => 'Disahkan',
                ]);
                $certificate->update([
                    'nomor_bak' => 'BAK-'.now()->format('Y').'-'.str_pad((string) $certificate->id_bak, 6, '0', STR_PAD_LEFT),
                ]);
                $locked->update(['status' => 'Diteruskan ke Logistik']);
            }
            $this->recordActivity($user, 'Klaim', 'Verifikasi klaim');
        });

        return (new ClaimResource($claim->refresh()->load(self::EAGER_LOAD)))->response();
    }

    public function resolve(Request $request, Klaim $claim): JsonResponse
    {
        $values = $request->validate(['hasil' => ['required', 'in:Selesai,Dikembalikan']]);
        DB::transaction(function () use ($claim, $values, $request) {
            $locked = Klaim::query()->whereKey($claim->id_klaim)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'Diteruskan ke Logistik', 422, 'Klaim belum diteruskan ke Logistik.');
            $locked->update([
                'status' => $values['hasil'],
                'tanggal_penyelesaian' => $values['hasil'] === 'Selesai' ? now() : null,
            ]);
            $this->recordActivity($request->user(), 'Klaim', 'Proses penyelesaian');
        });

        return (new ClaimResource($claim->refresh()->load(self::EAGER_LOAD)))->response();
    }

    public function photo(Request $request, Klaim $claim, int $photo): BinaryFileResponse
    {
        $this->authorizeClaim($request, $claim);
        $evidence = FotoBukti::query()->whereKey($photo)->whereHas(
            'detailKlaim', fn ($query) => $query->where('id_klaim', $claim->id_klaim)
        )->firstOrFail();
        abort_unless(Storage::disk('local')->exists($evidence->path_berkas), 404);

        return response()->file(Storage::disk('local')->path($evidence->path_berkas));
    }

    public function signature(Request $request, Klaim $claim, int $certificate): BinaryFileResponse
    {
        $this->authorizeClaim($request, $claim);
        $document = BeritaAcaraKerusakan::query()->whereKey($certificate)->where('id_klaim', $claim->id_klaim)->firstOrFail();
        abort_unless(Storage::disk('local')->exists($document->tanda_tangan_digital), 404);

        return response()->file(Storage::disk('local')->path($document->tanda_tangan_digital));
    }

    private function authorizeClaim(Request $request, Klaim $claim): void
    {
        $user = $request->user();
        if ($user->peran->nama_peran === 'Distributor') {
            abort_unless((int) $claim->penerimaan()->with('pengiriman.po.forecast')->firstOrFail()
                ->pengiriman->po->forecast->id_distributor === (int) $user->id_distributor, 403,
                'Klaim bukan milik perusahaan Anda.');
        }
    }

    private function recordActivity(Pengguna $user, string $module, string $action): void
    {
        LogAktivitas::query()->create([
            'id_pengguna' => $user->id_pengguna,
            'modul' => $module,
            'aksi' => $action,
            'waktu' => now(),
        ]);
    }
}
