<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClaimResource;
use App\Models\BeritaAcaraKerusakan;
use App\Models\FotoBukti;
use App\Models\Klaim;
use App\Services\KlaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            ->when($user->peran->nama_peran === 'Distributor', fn ($query) => $query->milikDistributor((int) $user->id_distributor))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('tanggal_pengajuan')->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return ClaimResource::collection($claims)->response();
    }

    public function show(Request $request, Klaim $claim): JsonResponse
    {
        $this->authorizeClaim($request, $claim);

        return (new ClaimResource($claim->load(self::EAGER_LOAD)))->response();
    }

    /** Ajukan klaim baru, atau ajukan ulang klaim berstatus Dikembalikan untuk kedatangan yang sama. */
    public function store(Request $request, KlaimService $service): JsonResponse
    {
        $values = $request->validate([
            'id_penerimaan' => ['required', 'integer', 'exists:penerimaan_barang,id_penerimaan'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_detail_penerimaan' => ['required', 'integer'],
            'items.*.jenis_kerusakan' => ['required', Rule::in(array_keys(KlaimService::JENIS_KERUSAKAN))],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'items.*.photos' => ['sometimes', 'array', 'max:8'],
            'items.*.photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $items = collect($values['items'])->map(fn (array $item, int $index) => [
            'id_detail_penerimaan' => (int) $item['id_detail_penerimaan'],
            'jenis_kerusakan' => $item['jenis_kerusakan'],
            'jumlah' => (int) $item['jumlah'],
            'foto' => $request->file("items.{$index}.photos", []),
        ])->all();
        $user = $request->user();
        $claim = $service->ajukan((int) $values['id_penerimaan'], (int) $user->id_distributor, $items, $user);

        return (new ClaimResource($claim->load(self::EAGER_LOAD)))->response()->setStatusCode(201);
    }

    public function scheduleVisit(Request $request, Klaim $claim, KlaimService $service): JsonResponse
    {
        $values = $request->validate(['jadwal_kunjungan' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']]);
        $service->jadwalkanKunjungan($claim, $values['jadwal_kunjungan'], $request->user());

        return (new ClaimResource($claim->refresh()->load(self::EAGER_LOAD)))->response();
    }

    /** Simpan hasil pemeriksaan fisik dan sahkan Berita Acara Kerusakan dalam satu langkah. */
    public function verify(Request $request, Klaim $claim, KlaimService $service): JsonResponse
    {
        $values = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_detail_klaim' => ['required', 'integer', 'distinct'],
            'items.*.status_verifikasi' => ['required', Rule::in([KlaimService::VERIFIKASI_SESUAI, KlaimService::VERIFIKASI_TIDAK_SESUAI])],
            'items.*.jumlah_terverifikasi' => ['nullable', 'integer', 'min:0'],
            'items.*.catatan' => ['nullable', 'string', 'max:255'],
            'items.*.photos' => ['sometimes', 'array', 'max:8'],
            'items.*.photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'signature' => ['required', 'image', 'mimes:png', 'max:2048'],
        ]);
        $hasil = collect($values['items'])->keyBy('id_detail_klaim')->all();
        $fotoLokasi = collect($values['items'])->mapWithKeys(fn (array $item, int $index) => [
            $item['id_detail_klaim'] => $request->file("items.{$index}.photos", []),
        ])->all();
        DB::transaction(function () use ($service, $claim, $hasil, $fotoLokasi, $request) {
            $service->simpanVerifikasi($claim, $hasil, $fotoLokasi, $request->user());
            $service->sahkan($claim, $request->file('signature')->get(), $request->user());
        });

        return (new ClaimResource($claim->refresh()->load(self::EAGER_LOAD)))->response();
    }

    public function returnToDistributor(Request $request, Klaim $claim, KlaimService $service): JsonResponse
    {
        $values = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);
        $service->kembalikanKeDistributor($claim, $values['catatan'], $request->user());

        return (new ClaimResource($claim->refresh()->load(self::EAGER_LOAD)))->response();
    }

    public function resolve(Request $request, Klaim $claim, KlaimService $service): JsonResponse
    {
        $values = $request->validate([
            'hasil' => ['required', 'in:Selesai,Kembalikan ke SAM'],
            'bentuk_penyelesaian' => ['required_if:hasil,Selesai', 'nullable', Rule::in(KlaimService::BENTUK_PENYELESAIAN)],
            'catatan' => ['required_if:hasil,Kembalikan ke SAM', 'nullable', 'string', 'max:1000'],
        ]);
        if ($values['hasil'] === 'Selesai') {
            $service->selesaikan($claim, $values['bentuk_penyelesaian'], $values['catatan'] ?? null, $request->user());
        } else {
            $service->kembalikanKeSam($claim, $values['catatan'], $request->user());
        }

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
}
