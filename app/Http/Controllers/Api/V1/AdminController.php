<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DistributorResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\Klaim;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\Pengiriman;
use App\Models\Peran;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\TahunFiskal;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function summary(): JsonResponse
    {
        return response()->json(['data' => [
            'users' => Pengguna::query()->count(),
            'active_distributors' => Distributor::query()->where('status_aktif', true)->count(),
            'products' => Produk::query()->count(),
            'forecasts' => Forecast::query()->count(),
            'purchase_orders' => PurchaseOrder::query()->count(),
            'shipments' => Pengiriman::query()->count(),
            'claims' => Klaim::query()->count(),
        ]]);
    }

    public function roles(): JsonResponse
    {
        return response()->json(['data' => Peran::query()->orderBy('nama_peran')->get([
            'id_peran', 'nama_peran', 'deskripsi',
        ])]);
    }

    public function users(Request $request): JsonResponse
    {
        $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $users = Pengguna::query()->with(['peran', 'distributor'])
            ->when($request->boolean('active_only'), fn ($query) => $query->where('status_aktif', true))
            ->orderBy('nama')->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return UserResource::collection($users)->response();
    }

    public function storeUser(Request $request): JsonResponse
    {
        $values = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:254', 'unique:pengguna,email'],
            'kata_sandi' => ['required', 'string', 'min:10', 'max:255'],
            'id_peran' => ['required', 'integer', 'exists:peran,id_peran'],
            'status_aktif' => ['sometimes', 'boolean'],
            'id_distributor' => ['nullable', 'integer', 'exists:distributor,id_distributor', 'unique:pengguna,id_distributor'],
        ]);
        $values['status_aktif'] ??= true;
        $role = Peran::query()->findOrFail($values['id_peran']);
        $distributorRole = mb_strtolower($role->nama_peran) === 'distributor';
        abort_if($distributorRole && empty($values['id_distributor']), 422, 'Akun Distributor harus terhubung ke perusahaan.');
        abort_if(! $distributorRole && ! empty($values['id_distributor']), 422, 'Akun internal tidak boleh terhubung ke perusahaan Distributor.');

        $user = DB::transaction(function () use ($values) {
            $user = Pengguna::query()->create($values);
            $this->recordActivity((int) request()->user()->id_pengguna, 'Pengguna', 'Buat akun');

            return $user;
        });

        return (new UserResource($user->load(['peran', 'distributor'])))->response()->setStatusCode(201);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = Pengguna::query()->findOrFail($id);
        $values = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:254', Rule::unique('pengguna', 'email')->ignore($user->id_pengguna, 'id_pengguna')],
            'kata_sandi' => ['sometimes', 'required', 'string', 'min:10', 'max:255'],
            'id_peran' => ['sometimes', 'required', 'integer', 'exists:peran,id_peran'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
            'id_distributor' => ['sometimes', 'nullable', 'integer', 'exists:distributor,id_distributor', Rule::unique('pengguna', 'id_distributor')->ignore($user->id_pengguna, 'id_pengguna')],
        ]);
        $role = isset($values['id_peran']) ? Peran::query()->findOrFail($values['id_peran']) : $user->peran;
        $distributorId = array_key_exists('id_distributor', $values) ? $values['id_distributor'] : $user->id_distributor;
        $distributorRole = mb_strtolower($role->nama_peran) === 'distributor';
        abort_if($distributorRole && ! $distributorId, 422, 'Akun Distributor harus terhubung ke perusahaan.');
        abort_if(! $distributorRole && $distributorId, 422, 'Akun internal tidak boleh terhubung ke perusahaan Distributor.');
        $user->fill($values)->save();
        $this->recordActivity((int) request()->user()->id_pengguna, 'Pengguna', 'Ubah akun');

        return (new UserResource($user->load(['peran', 'distributor'])))->response();
    }

    public function distributors(Request $request): JsonResponse
    {
        $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);

        return DistributorResource::collection(
            Distributor::query()->with('wilayah')->orderBy('nama')->paginate(max(1, min($request->integer('per_page', 50), 100)))
        )->response();
    }

    public function storeDistributor(Request $request): JsonResponse
    {
        $values = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'id_wilayah' => ['required', 'integer', 'exists:wilayah,id_wilayah'],
            'kontak' => ['required', 'string', 'max:60'],
            'alamat_gudang' => ['nullable', 'string'],
            'nomor_mou' => ['nullable', 'string', 'max:60'],
            'status_aktif' => ['sometimes', 'boolean'],
        ]);
        $values['status_aktif'] ??= true;
        $distributor = DB::transaction(function () use ($values) {
            $distributor = Distributor::query()->create($values);
            $this->recordActivity((int) request()->user()->id_pengguna, 'Master Distributor', 'Tambah');

            return $distributor;
        });

        return (new DistributorResource($distributor->load('wilayah')))->response()->setStatusCode(201);
    }

    public function updateDistributor(Request $request, int $id): JsonResponse
    {
        $distributor = Distributor::query()->findOrFail($id);
        $values = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'id_wilayah' => ['sometimes', 'required', 'integer', 'exists:wilayah,id_wilayah'],
            'kontak' => ['sometimes', 'required', 'string', 'max:60'],
            'alamat_gudang' => ['sometimes', 'nullable', 'string'],
            'nomor_mou' => ['sometimes', 'nullable', 'string', 'max:60'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
        ]);
        $distributor->fill($values)->save();
        $this->recordActivity((int) request()->user()->id_pengguna, 'Master Distributor', 'Ubah');

        return (new DistributorResource($distributor->load('wilayah')))->response();
    }

    public function products(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $products = Produk::query()->when($request->filled('q'), fn ($query) => $query->where(
            fn ($search) => $search->where('kode_sku', 'like', '%'.$request->input('q').'%')
                ->orWhere('nama_produk', 'like', '%'.$request->input('q').'%')
        ))->orderBy('kode_sku')->paginate(max(1, min($request->integer('per_page', 50), 100)));

        return ProductResource::collection($products)->response();
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $values = $request->validate([
            'kode_sku' => ['required', 'string', 'max:60', 'unique:produk,kode_sku'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'karton_per_kontainer' => ['required', 'integer', 'min:1'],
            'kategori' => ['nullable', 'string', 'max:40'],
        ]);
        $product = Produk::query()->create($values);
        $this->recordActivity((int) request()->user()->id_pengguna, 'Master Produk', 'Tambah');

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $product = Produk::query()->findOrFail($id);
        $values = $request->validate([
            'kode_sku' => ['sometimes', 'required', 'string', 'max:60', Rule::unique('produk', 'kode_sku')->ignore($product->id_produk, 'id_produk')],
            'nama_produk' => ['sometimes', 'required', 'string', 'max:150'],
            'harga' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'karton_per_kontainer' => ['sometimes', 'required', 'integer', 'min:1'],
            'kategori' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);
        $product->fill($values)->save();
        $this->recordActivity((int) request()->user()->id_pengguna, 'Master Produk', 'Ubah');

        return (new ProductResource($product))->response();
    }

    public function fiscalYears(): JsonResponse
    {
        return response()->json(['data' => TahunFiskal::query()->orderByDesc('tanggal_mulai')->get()]);
    }

    public function storeFiscalYear(Request $request): JsonResponse
    {
        $values = $request->validate([
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);
        abort_if(TahunFiskal::query()->whereDate('tanggal_mulai', '<=', $values['tanggal_selesai'])
            ->whereDate('tanggal_selesai', '>=', $values['tanggal_mulai'])->exists(), 422, 'Rentang tahun fiskal bertumpang tindih dengan data yang ada.');

        return response()->json(['data' => TahunFiskal::query()->create($values)], 201);
    }

    public function updateFiscalYear(Request $request, int $id): JsonResponse
    {
        $year = TahunFiskal::query()->findOrFail($id);
        $values = $request->validate([
            'tanggal_mulai' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);
        $start = $values['tanggal_mulai'] ?? $year->tanggal_mulai->format('Y-m-d');
        $end = $values['tanggal_selesai'] ?? $year->tanggal_selesai->format('Y-m-d');
        abort_if($end < $start, 422, 'Tanggal akhir harus sama atau setelah tanggal mulai.');
        abort_if(TahunFiskal::query()->where('id_tahun_fiskal', '!=', $year->id_tahun_fiskal)
            ->whereDate('tanggal_mulai', '<=', $end)->whereDate('tanggal_selesai', '>=', $start)->exists(),
            422, 'Rentang tahun fiskal bertumpang tindih dengan data yang ada.');
        $year->fill($values)->save();

        return response()->json(['data' => $year->refresh()]);
    }

    public function regions(): JsonResponse
    {
        return response()->json(['data' => Wilayah::query()->orderBy('nama_wilayah')->get()]);
    }

    public function storeRegion(Request $request): JsonResponse
    {
        $values = $request->validate(['nama_wilayah' => ['required', 'string', 'max:150']]);

        return response()->json(['data' => Wilayah::query()->create($values)], 201);
    }

    public function activityLogs(Request $request): JsonResponse
    {
        $logs = LogAktivitas::query()->with('pengguna:id_pengguna,nama')->latest('waktu')
            ->when($request->filled('modul'), fn ($query) => $query->where('modul', $request->input('modul')))
            ->paginate(min($request->integer('per_page', 50), 100));

        return response()->json(['data' => $logs]);
    }

    private function recordActivity(int $userId, string $module, string $action): void
    {
        LogAktivitas::query()->create([
            'id_pengguna' => $userId,
            'modul' => $module,
            'aksi' => $action,
            'waktu' => now(),
        ]);
    }
}
