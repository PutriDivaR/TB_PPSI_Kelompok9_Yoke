<?php

namespace Tests\Feature;

use App\Models\DetailForecast;
use App\Models\DetailPo;
use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\SellOut;
use App\Models\StokGudang;
use App\Models\TahunFiskal;
use Carbon\CarbonImmutable;
use Tests\Concerns\MembangunSkemaYoke;
use Tests\TestCase;

class EpoWorkflowTest extends TestCase
{
    use MembangunSkemaYoke;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-11-24 10:00:00'));
        $this->bangunSkemaYoke();
    }

    protected function tearDown(): void
    {
        $this->bongkarSkemaYoke();
        $this->travelBack();
        parent::tearDown();
    }

    public function test_forecast_api_rejects_approved_quantity_above_the_distributor_request(): void
    {
        [$forecast, $detail] = $this->buatForecast('Diajukan', 1500);
        $logistik = $this->buatPengguna('Logistik Pusat');

        $this->actingAs($logistik)->postJson('/api/v1/forecasts/'.$forecast->id_forecast.'/approval', [
            'items' => [['id_detail_forecast' => $detail->id_detail_forecast, 'jumlah_disetujui' => 1501]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseHas('forecast', ['id_forecast' => $forecast->id_forecast, 'status' => 'Diajukan']);
        $this->assertDatabaseHas('detail_forecast', [
            'id_detail_forecast' => $detail->id_detail_forecast,
            'jumlah_disetujui' => null,
        ]);
    }

    public function test_forecast_api_rejects_quota_that_exceeds_monthly_factory_capacity(): void
    {
        [$existingForecast, $existingDetail] = $this->buatForecast('Disetujui', 29000, 29000);
        [$forecast, $detail] = $this->buatForecast('Diajukan', 2000);
        $logistik = $this->buatPengguna('Logistik Pusat');

        $this->actingAs($logistik)->postJson('/api/v1/forecasts/'.$forecast->id_forecast.'/approval', [
            'items' => [['id_detail_forecast' => $detail->id_detail_forecast, 'jumlah_disetujui' => 2000]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseHas('detail_forecast', [
            'id_detail_forecast' => $existingDetail->id_detail_forecast,
            'jumlah_disetujui' => 29000,
        ]);
        $this->assertDatabaseHas('forecast', ['id_forecast' => $forecast->id_forecast, 'status' => 'Diajukan']);
        $this->assertSame('Disetujui', $existingForecast->fresh()->status);
    }

    public function test_forecast_api_requires_an_adjustment_note_when_quota_is_reduced(): void
    {
        [$forecast, $detail] = $this->buatForecast('Diajukan', 3000);
        $logistik = $this->buatPengguna('Logistik Pusat');

        $this->actingAs($logistik)->postJson('/api/v1/forecasts/'.$forecast->id_forecast.'/approval', [
            'items' => [['id_detail_forecast' => $detail->id_detail_forecast, 'jumlah_disetujui' => 2500]],
        ])->assertUnprocessable()->assertJsonValidationErrors('catatan');

        $this->assertDatabaseHas('forecast', ['id_forecast' => $forecast->id_forecast, 'status' => 'Diajukan']);
    }

    public function test_forecast_api_approval_persists_quota_and_authenticated_approver(): void
    {
        [$forecast, $detail] = $this->buatForecast('Diajukan', 2500);
        $logistik = $this->buatPengguna('Logistik Pusat');

        $this->actingAs($logistik)->postJson('/api/v1/forecasts/'.$forecast->id_forecast.'/approval', [
            'items' => [['id_detail_forecast' => $detail->id_detail_forecast, 'jumlah_disetujui' => 2500]],
        ])->assertOk()->assertJsonPath('data.status', 'Disetujui');

        $this->assertDatabaseHas('forecast', [
            'id_forecast' => $forecast->id_forecast,
            'status' => 'Disetujui',
            'id_penyetuju' => $logistik->id_pengguna,
        ]);
        $this->assertDatabaseHas('detail_forecast', [
            'id_detail_forecast' => $detail->id_detail_forecast,
            'jumlah_disetujui' => 2500,
        ]);
    }

    public function test_purchase_order_api_rejects_submission_below_minimum_order(): void
    {
        [$forecast, $detail] = $this->buatForecast('Disetujui', 3000, 3000);
        $distributor = $forecast->distributor;
        $user = $this->buatPengguna('Distributor', $distributor);

        $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
            'id_forecast' => $forecast->id_forecast,
            'items' => [['id_produk' => $detail->id_produk, 'jumlah_kontainer' => 1500]],
            'submit' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseMissing('purchase_order', ['id_forecast' => $forecast->id_forecast]);
    }

    public function test_purchase_order_api_rejects_submission_above_remaining_product_quota(): void
    {
        [$forecast, $detail] = $this->buatForecast('Disetujui', 5000, 4000);
        $this->buatPo($forecast, $detail, 2500);
        $user = $this->buatPengguna('Distributor', $forecast->distributor);

        $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
            'id_forecast' => $forecast->id_forecast,
            'items' => [['id_produk' => $detail->id_produk, 'jumlah_kontainer' => 2000]],
            'submit' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertSame(1, PurchaseOrder::query()->where('id_forecast', $forecast->id_forecast)->count());
    }

    public function test_purchase_order_api_approval_rechecks_remaining_quota_against_other_orders(): void
    {
        [$forecast, $detail] = $this->buatForecast('Disetujui', 5000, 4000);
        $this->buatPo($forecast, $detail, 2500);
        $order = $this->buatPo($forecast, $detail, 2000);
        $logistik = $this->buatPengguna('Logistik Pusat');

        $this->actingAs($logistik)->postJson('/api/v1/purchase-orders/'.$order->id_po.'/approval')
            ->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseHas('purchase_order', ['id_po' => $order->id_po, 'status' => 'Diajukan']);
    }

    public function test_purchase_order_api_submission_and_approval_complete_the_order_flow(): void
    {
        [$forecast, $detail] = $this->buatForecast('Disetujui', 3000, 3000);
        $distributor = $this->buatPengguna('Distributor', $forecast->distributor);
        $logistik = $this->buatPengguna('Logistik Pusat');

        $response = $this->actingAs($distributor)->postJson('/api/v1/purchase-orders', [
            'id_forecast' => $forecast->id_forecast,
            'items' => [['id_produk' => $detail->id_produk, 'jumlah_kontainer' => 2000]],
            'submit' => true,
        ])->assertCreated()->assertJsonPath('data.status', 'Diajukan');

        $orderId = $response->json('data.id');
        $this->actingAs($logistik)->postJson('/api/v1/purchase-orders/'.$orderId.'/approval')
            ->assertOk()->assertJsonPath('data.status', 'Disetujui');

        $this->assertDatabaseHas('purchase_order', [
            'id_po' => $orderId,
            'status' => 'Disetujui',
            'id_penyetuju' => $logistik->id_pengguna,
        ]);
    }

    public function test_logistics_approval_records_the_authenticated_approver(): void
    {
        $this->buatPengguna('Logistik Pusat', null, 'Logistik pertama');
        $approver = $this->buatPengguna('Logistik Pusat', null, 'Logistik yang login');
        [$forecast] = $this->buatForecast('Diajukan', 2500);

        $this->actingAs($approver)->post(route('e-po.logistik.tetapkan-kuota', $forecast), [
            'aksi' => 'setujui_semua',
        ])->assertRedirect(route('e-po.logistik.antrean'));

        $this->assertDatabaseHas('forecast', [
            'id_forecast' => $forecast->id_forecast,
            'status' => 'Disetujui',
            'id_penyetuju' => $approver->id_pengguna,
        ]);
    }

    public function test_logistics_po_approval_records_the_authenticated_approver(): void
    {
        $this->buatPengguna('Logistik Pusat', null, 'Logistik pertama');
        $approver = $this->buatPengguna('Logistik Pusat', null, 'Logistik yang login');
        [$forecast, $detail] = $this->buatForecast('Disetujui', 3000, 3000);
        $order = $this->buatPo($forecast, $detail, 2000);

        $this->actingAs($approver)->post(route('e-po.logistik.setujui-epo', $order), [
            'konfirmasi' => '1',
        ])->assertRedirect(route('e-po.logistik.verifikasi-epo', $order));

        $this->assertDatabaseHas('purchase_order', [
            'id_po' => $order->id_po,
            'status' => 'Disetujui',
            'id_penyetuju' => $approver->id_pengguna,
        ]);
    }

    public function test_logistics_processing_actions_update_status_and_log_the_authenticated_user(): void
    {
        $logistik = $this->buatPengguna('Logistik Pusat');
        [$forecast, $detail] = $this->buatForecast('Diajukan', 2500);
        $order = $this->buatPo($forecast, $detail, 2000);

        $this->actingAs($logistik)->post(route('e-po.logistik.mulai-forecast', $forecast))
            ->assertRedirect(route('e-po.logistik.detail-forecast', $forecast));
        $this->actingAs($logistik)->post(route('e-po.logistik.mulai-epo', $order))
            ->assertRedirect(route('e-po.logistik.verifikasi-epo', $order));

        $this->assertDatabaseHas('forecast', ['id_forecast' => $forecast->id_forecast, 'status' => 'Diproses']);
        $this->assertDatabaseHas('purchase_order', ['id_po' => $order->id_po, 'status' => 'Diproses']);
        $this->assertSame(2, LogAktivitas::query()
            ->where('id_pengguna', $logistik->id_pengguna)
            ->where('modul', 'E-PO')
            ->count());
    }

    public function test_api_recommendations_match_weighted_carton_calculation_used_by_the_web_form(): void
    {
        $distributor = Distributor::factory()->create();
        $user = $this->buatPengguna('Distributor', $distributor);
        $product = Produk::factory()->create();
        foreach ([
            ['tanggal' => '2025-12-15', 'jumlah' => 100],
            ['tanggal' => '2026-01-15', 'jumlah' => 200],
            ['tanggal' => '2026-02-15', 'jumlah' => 300],
        ] as $sale) {
            SellOut::factory()->create([
                'id_distributor' => $distributor->id_distributor,
                'id_produk' => $product->id_produk,
                'tanggal' => $sale['tanggal'],
                'jumlah' => $sale['jumlah'],
            ]);
        }
        StokGudang::factory()->create([
            'id_distributor' => $distributor->id_distributor,
            'id_produk' => $product->id_produk,
            'periode' => '2026-02-28',
            'stok_fisik' => 100,
            'stok_layak_jual' => 100,
        ]);

        $this->actingAs($user)->getJson('/api/v1/forecasts/recommendations?periode=2027-02')
            ->assertOk()
            ->assertJsonPath('data.items.0.id_produk', $product->id_produk)
            ->assertJsonPath('data.items.0.rata_rata_sell_out', 233.3)
            ->assertJsonPath('data.items.0.rekomendasi', 157);
    }

    /**
     * @return array{Forecast, DetailForecast}
     */
    private function buatForecast(string $status, int $usulan, ?int $disetujui = null): array
    {
        $distributor = Distributor::factory()->create(['nomor_mou' => 'MOU-UJI']);
        $fiscalYear = TahunFiskal::factory()->create();
        $forecast = Forecast::factory()->create([
            'id_distributor' => $distributor->id_distributor,
            'id_tahun_fiskal' => $fiscalYear->id_tahun_fiskal,
            'periode' => '2027-02-01',
            'status' => $status,
        ]);
        $detail = DetailForecast::factory()->create([
            'id_forecast' => $forecast->id_forecast,
            'id_produk' => Produk::factory(),
            'jumlah_kontainer' => $usulan,
            'jumlah_disetujui' => $disetujui,
        ]);

        return [$forecast, $detail];
    }

    private function buatPo(Forecast $forecast, DetailForecast $detail, int $jumlah): PurchaseOrder
    {
        $order = PurchaseOrder::factory()->create([
            'id_forecast' => $forecast->id_forecast,
            'status' => 'Diajukan',
        ]);
        DetailPo::factory()->create([
            'id_po' => $order->id_po,
            'id_produk' => $detail->id_produk,
            'jumlah_kontainer' => $jumlah,
        ]);

        return $order;
    }

    private function buatPengguna(string $peran, ?Distributor $distributor = null, ?string $nama = null): Pengguna
    {
        return Pengguna::factory()->create([
            'nama' => $nama ?? 'Pengguna '.$peran,
            'id_peran' => Peran::factory()->create(['nama_peran' => $peran])->id_peran,
            'id_distributor' => $distributor?->id_distributor,
        ]);
    }
}
