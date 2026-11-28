<?php

namespace Tests\Feature;

use App\Models\DetailForecast;
use App\Models\DetailPenerimaan;
use App\Models\DetailPengiriman;
use App\Models\DetailPo;
use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\PenerimaanBarang;
use App\Models\Pengguna;
use App\Models\Pengiriman;
use App\Models\Peran;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\SellIn;
use App\Models\SellOut;
use App\Models\StokGudang;
use App\Models\TahunFiskal;
use App\Services\PelaporanService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PelaporanDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-11-13 12:00:00'));
        if (DB::connection()->getDriverName() === 'mysql') {
            if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
                throw new \RuntimeException('Pengujian MySQL hanya boleh memakai database berakhiran _test.');
            }
            if (! Schema::hasTable('produk')) {
                $this->assertSame(0, Artisan::call('yoke:database-install'));
            }
        } else {
            $schema = json_decode(file_get_contents(database_path('schema/schema_erd.json')), true, flags: JSON_THROW_ON_ERROR);
            $tables = collect($schema['tables'])->keyBy('name');
            foreach ($tables as $name => $table) {
                Schema::create($name, function (Blueprint $blueprint) use ($table, $schema, $tables, $name) {
                    foreach ($table['attrs'] as $attribute) {
                        $column = $attribute['name'];
                        if ($attribute['pk']) {
                            $blueprint->bigIncrements($column);

                            continue;
                        }
                        $type = $attribute['type'];
                        $definition = match (true) {
                            $type === 'BIGINT' => $blueprint->unsignedBigInteger($column),
                            $type === 'INT' => $blueprint->integer($column),
                            $type === 'BOOLEAN' => $blueprint->boolean($column),
                            $type === 'DATE' => $blueprint->date($column),
                            $type === 'DATETIME' => $blueprint->dateTime($column),
                            $type === 'TEXT' => $blueprint->text($column),
                            str_starts_with($type, 'DECIMAL') => $blueprint->decimal($column, 15, 2),
                            default => $blueprint->string($column, (int) preg_replace('/\D/', '', $type)),
                        };
                        if ($attribute['nullable']) {
                            $definition->nullable();
                        }
                        if ($attribute['unique']) {
                            $definition->unique();
                        }
                        if ($attribute['fk']) {
                            $parent = $attribute['fk']['parent'];
                            $parentKey = collect($tables[$parent]['attrs'])->firstWhere('pk', true)['name'];
                            $blueprint->foreign($column)->references($parentKey)->on($parent)->restrictOnDelete()->restrictOnUpdate();
                        }
                    }
                    if (isset($schema['multi_unique'][$name])) {
                        $blueprint->unique($schema['multi_unique'][$name]);
                    }
                });
            }
        }
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        $this->travelBack();
        parent::tearDown();
    }

    private function penggunaDistributor(Distributor $distributor): Pengguna
    {
        $role = Peran::factory()->create(['nama_peran' => 'Distributor']);

        return Pengguna::factory()->create([
            'id_peran' => $role->getKey(),
            'id_distributor' => $distributor->getKey(),
        ]);
    }

    public function test_reporting_api_requires_an_authenticated_user(): void
    {
        $this->getJson('/api/v1/reports/summary')->assertUnauthorized();
    }

    public function test_reporting_api_filters_dashboard_summary_and_export_by_product(): void
    {
        $company = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);
        $selectedProduct = Produk::factory()->create();
        $otherProduct = Produk::factory()->create();
        SellOut::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $selectedProduct->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 7,
        ]);
        SellOut::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $otherProduct->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 13,
        ]);
        $query = '?mulai=2026-11-01&selesai=2026-11-13&id_produk='.$selectedProduct->getKey();

        $this->actingAs($user)->getJson('/api/v1/reports/summary'.$query)
            ->assertOk()->assertJsonPath('meta.count', 1)->assertJsonPath('data.0.id_produk', $selectedProduct->getKey());
        $this->getJson('/api/v1/reports/dashboard'.$query)
            ->assertOk()->assertJsonPath('data.totals.sell_out', 7)
            ->assertJsonPath('data.stock_reconciliation.0.id_produk', $selectedProduct->getKey());
        $export = $this->get('/api/v1/reports/export'.$query)->assertOk()->streamedContent();
        $this->assertStringContainsString($selectedProduct->kode_sku, $export);
        $this->assertStringNotContainsString($otherProduct->kode_sku, $export);
    }

    public function test_distributor_reporting_api_is_scoped_to_its_company(): void
    {
        $company = Distributor::factory()->create();
        $other = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);

        $this->actingAs($user)->getJson('/api/v1/reports/summary?id_distributor='.$other->getKey())
            ->assertForbidden();
    }

    public function test_reporting_api_persists_sell_out_and_stock_for_the_authenticated_company(): void
    {
        $company = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);
        $product = Produk::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/reports/sell-out', [
            'id_produk' => $product->getKey(), 'kanal' => 'Retail', 'jumlah' => 8, 'tanggal' => '2026-11-12',
        ])->assertCreated()->assertJsonPath('data.id_distributor', $company->getKey());
        $this->postJson('/api/v1/reports/stock', [
            'id_produk' => $product->getKey(), 'periode' => '2026-11-13', 'stok_fisik' => 20, 'stok_layak_jual' => 18,
        ])->assertCreated()->assertJsonPath('data.id_distributor', $company->getKey());

        $this->assertDatabaseCount('sell_out', 1);
        $this->assertDatabaseCount('stok_gudang', 1);
        $this->assertDatabaseCount('log_aktivitas', 2);
        $this->assertDatabaseHas('sell_out', [
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'jumlah' => 8,
        ]);
    }

    public function test_sell_out_history_is_filtered_scoped_and_paginated(): void
    {
        $company = Distributor::factory()->create();
        $otherCompany = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);
        $product = Produk::factory()->create();
        $otherProduct = Produk::factory()->create();
        SellOut::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'tanggal' => '2026-11-10', 'jumlah' => 10,
        ]);
        SellOut::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 12,
        ]);
        SellOut::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $otherProduct->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 99,
        ]);
        SellOut::factory()->create([
            'id_distributor' => $otherCompany->getKey(), 'id_produk' => $product->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 88,
        ]);
        $url = '/api/v1/reports/sell-out?mulai=2026-11-01&selesai=2026-11-13&id_produk='.$product->getKey().'&per_page=1';

        $this->actingAs($user)->getJson($url.'&page=1')->assertOk()
            ->assertJsonPath('meta.total', 2)->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.jumlah', 12)
            ->assertJsonPath('data.0.distributor.nama', $company->nama)
            ->assertJsonPath('data.0.produk.kode_sku', $product->kode_sku);
        $this->getJson($url.'&page=2')->assertOk()
            ->assertJsonPath('data.0.jumlah', 10)
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_stock_history_returns_filtered_snapshots_and_validates_page_size(): void
    {
        $company = Distributor::factory()->create();
        $otherCompany = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);
        $product = Produk::factory()->create();
        $otherProduct = Produk::factory()->create();
        StokGudang::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-10',
            'stok_fisik' => 30, 'stok_layak_jual' => 25,
        ]);
        StokGudang::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-12',
            'stok_fisik' => 40, 'stok_layak_jual' => 35,
        ]);
        StokGudang::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $otherProduct->getKey(), 'periode' => '2026-11-12',
            'stok_fisik' => 90, 'stok_layak_jual' => 80,
        ]);
        StokGudang::factory()->create([
            'id_distributor' => $otherCompany->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-12',
            'stok_fisik' => 70, 'stok_layak_jual' => 65,
        ]);
        $url = '/api/v1/reports/stock?mulai=2026-11-01&selesai=2026-11-13&id_produk='.$product->getKey();

        $this->actingAs($user)->getJson($url)->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.periode', '2026-11-12')
            ->assertJsonPath('data.0.stok_fisik', 40)
            ->assertJsonPath('data.0.stok_layak_jual', 35)
            ->assertJsonPath('data.0.distributor.nama', $company->nama);
        $this->getJson($url.'&per_page=101')->assertUnprocessable();
    }

    public function test_reporting_api_rejects_a_period_outside_selected_fiscal_year(): void
    {
        $company = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);
        $year = TahunFiskal::factory()->create([
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2027-09-30',
        ]);

        $this->actingAs($user)->getJson('/api/v1/reports/summary?mulai=2026-09-30&selesai=2026-10-31&id_tahun_fiskal='.$year->getKey())
            ->assertUnprocessable();
    }

    public function test_preview_page_loads_as_a_client_of_the_reporting_api(): void
    {
        $company = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);

        $this->actingAs($user)->get('/pelaporan')->assertOk()
            ->assertSee('/api/v1/reports/dashboard')
            ->assertSee('/api/v1/reports/sell-out')
            ->assertSee('/api/v1/reports/stock')
            ->assertSee('Simpan Sell-Out')
            ->assertSee('Simpan Snapshot');
        $this->post('/pelaporan/sell-out')->assertNotFound();
        $this->get('/pelaporan/ekspor')->assertNotFound();
    }

    public function test_negative_sell_out_is_rejected_without_persistence(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $user = $this->penggunaDistributor($company);
        $this->actingAs($user)->postJson('/api/v1/reports/sell-out', [
            'id_produk' => $product->getKey(), 'kanal' => 'Retail', 'jumlah' => -1, 'tanggal' => '2026-11-12',
        ])->assertUnprocessable()->assertJsonValidationErrors('jumlah');
        $this->assertDatabaseCount('sell_out', 0);
    }

    public function test_saleable_stock_above_physical_stock_is_rejected(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $user = $this->penggunaDistributor($company);
        $this->actingAs($user)->postJson('/api/v1/reports/stock', [
            'id_produk' => $product->getKey(), 'periode' => '2026-11-12', 'stok_fisik' => 100, 'stok_layak_jual' => 101,
        ])->assertUnprocessable()->assertJsonValidationErrors('stok_layak_jual');
        $this->assertDatabaseCount('stok_gudang', 0);
    }

    public function test_stock_snapshot_is_updated_without_duplicate_rows(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $user = $this->penggunaDistributor($company);
        $payload = ['id_produk' => $product->getKey(), 'periode' => '2026-11-12', 'stok_fisik' => 100, 'stok_layak_jual' => 95];
        $this->actingAs($user)->postJson('/api/v1/reports/stock', $payload)->assertCreated();
        $payload['stok_fisik'] = 99;
        $payload['stok_layak_jual'] = 94;
        $this->postJson('/api/v1/reports/stock', $payload)->assertCreated();
        $this->assertDatabaseCount('stok_gudang', 1);
        $this->assertDatabaseHas('stok_gudang', [...$payload, 'id_distributor' => $company->getKey()]);
    }

    public function test_distributor_cannot_read_or_write_another_company(): void
    {
        $company = Distributor::factory()->create();
        $other = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $user = $this->penggunaDistributor($company);
        $this->actingAs($user)->getJson('/api/v1/reports/summary?id_distributor='.$other->getKey())->assertForbidden();
        $this->postJson('/api/v1/reports/sell-out', [
            'id_distributor' => $other->getKey(), 'id_produk' => $product->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 5, 'kanal' => 'Retail',
        ])->assertCreated();
        $this->assertDatabaseHas('sell_out', ['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'jumlah' => 5]);
        $this->assertDatabaseMissing('sell_out', ['id_distributor' => $other->getKey()]);
    }

    public function test_logistik_can_read_reports_but_cannot_submit_distributor_sales(): void
    {
        $role = Peran::factory()->create(['nama_peran' => 'Logistik Pusat']);
        $user = Pengguna::factory()->create(['id_peran' => $role->getKey()]);
        $this->actingAs($user)->getJson('/api/v1/reports/summary')->assertOk();
        $this->postJson('/api/v1/reports/sell-out', [])->assertForbidden();
    }

    public function test_production_environment_cannot_use_simulated_write_access(): void
    {
        $company = Distributor::factory()->create();
        $user = $this->penggunaDistributor($company);
        $this->app->instance('env', 'production');
        $this->actingAs($user)->get('/pelaporan')->assertForbidden();
    }

    public function test_report_uses_received_good_stock_and_latest_snapshot(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $forecast = Forecast::factory()->create(['id_distributor' => $company->getKey()]);
        DetailForecast::factory()->create(['id_forecast' => $forecast->getKey(), 'id_produk' => $product->getKey()]);
        $po = PurchaseOrder::factory()->create(['id_forecast' => $forecast->getKey()]);
        $poLine = DetailPo::factory()->create(['id_po' => $po->getKey(), 'id_produk' => $product->getKey()]);
        $shipment = Pengiriman::factory()->create(['id_po' => $po->getKey()]);
        $shipmentLine = DetailPengiriman::factory()->create(['id_pengiriman' => $shipment->getKey(), 'id_detail_po' => $poLine->getKey(), 'jumlah_dikirim' => 500]);
        $receipt = PenerimaanBarang::factory()->create(['id_pengiriman' => $shipment->getKey(), 'tanggal_terima' => '2026-11-05 10:00:00']);
        DetailPenerimaan::factory()->create(['id_penerimaan' => $receipt->getKey(), 'id_detail_pengiriman' => $shipmentLine->getKey(), 'jumlah_diterima' => 500, 'jumlah_baik' => 200]);
        SellIn::factory()->create(['id_detail_pengiriman' => $shipmentLine->getKey(), 'jumlah' => 500, 'tanggal' => '2026-11-01']);
        SellOut::factory()->create(['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'jumlah' => 100, 'tanggal' => '2026-11-10']);
        StokGudang::factory()->create(['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-10-31', 'stok_fisik' => 1100, 'stok_layak_jual' => 1000]);
        StokGudang::factory()->create(['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-11', 'stok_fisik' => 1400, 'stok_layak_jual' => 1100]);
        StokGudang::factory()->create(['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-13', 'stok_fisik' => 1400, 'stok_layak_jual' => 1100]);
        $row = app(PelaporanService::class)->summary('2026-11-01', '2026-11-13', $company->getKey())->sole();
        $this->assertSame(500, $row['sell_in']);
        $this->assertEquals(1100, $row['ekspektasi_layak_jual']);
        $this->assertSame(1400, $row['stok_fisik']);
        $this->assertSame(0, $row['selisih']);
        $this->assertSame('Sesuai', $row['status']);
    }

    public function test_missing_opening_balance_is_not_silently_assumed_zero(): void
    {
        $stock = StokGudang::factory()->create(['periode' => '2026-11-13']);
        $row = app(PelaporanService::class)->summary('2026-11-01', '2026-11-13', $stock->id_distributor)->sole();
        $this->assertNull($row['ekspektasi_layak_jual']);
        $this->assertNull($row['selisih']);
        $this->assertSame('Perlu saldo awal', $row['status']);
    }

    public function test_reconciliation_requires_a_snapshot_on_the_period_end_date(): void
    {
        $stock = StokGudang::factory()->create([
            'periode' => '2026-10-31', 'stok_fisik' => 90, 'stok_layak_jual' => 80,
        ]);

        $row = app(PelaporanService::class)->summary('2026-11-01', '2026-11-13', $stock->id_distributor)->sole();

        $this->assertSame('2026-10-31', $row['tanggal_stok']);
        $this->assertSame(80, $row['ekspektasi_layak_jual']);
        $this->assertNull($row['selisih']);
        $this->assertSame('Perlu stok akhir periode', $row['status']);
    }

    public function test_reconciliation_includes_good_receipt_on_period_end_and_excludes_damaged_units(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $forecast = Forecast::factory()->create(['id_distributor' => $company->getKey()]);
        DetailForecast::factory()->create(['id_forecast' => $forecast->getKey(), 'id_produk' => $product->getKey()]);
        $po = PurchaseOrder::factory()->create(['id_forecast' => $forecast->getKey()]);
        $poLine = DetailPo::factory()->create(['id_po' => $po->getKey(), 'id_produk' => $product->getKey()]);
        $shipment = Pengiriman::factory()->create(['id_po' => $po->getKey()]);
        $shipmentLine = DetailPengiriman::factory()->create([
            'id_pengiriman' => $shipment->getKey(), 'id_detail_po' => $poLine->getKey(), 'jumlah_dikirim' => 20,
        ]);
        $receipt = PenerimaanBarang::factory()->create([
            'id_pengiriman' => $shipment->getKey(), 'tanggal_terima' => '2026-11-13 23:59:59',
        ]);
        DetailPenerimaan::factory()->create([
            'id_penerimaan' => $receipt->getKey(), 'id_detail_pengiriman' => $shipmentLine->getKey(),
            'jumlah_diterima' => 20, 'jumlah_baik' => 15,
        ]);
        StokGudang::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-10-31',
            'stok_fisik' => 100, 'stok_layak_jual' => 100,
        ]);
        StokGudang::factory()->create([
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-13',
            'stok_fisik' => 115, 'stok_layak_jual' => 115,
        ]);

        $row = app(PelaporanService::class)->summary('2026-11-01', '2026-11-13', $company->getKey())->sole();

        $this->assertSame(15, $row['penerimaan_baik']);
        $this->assertSame(115, $row['ekspektasi_layak_jual']);
        $this->assertSame(0, $row['selisih']);
        $this->assertSame('Sesuai', $row['status']);
    }

    public function test_export_reads_real_rows_and_escapes_spreadsheet_formulas(): void
    {
        $company = Distributor::factory()->create(['nama' => '=1+1']);
        $product = Produk::factory()->create();
        SellOut::factory()->create(['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 25]);
        $user = $this->penggunaDistributor($company);
        $response = $this->actingAs($user)->get('/api/v1/reports/export?mulai=2026-11-01&selesai=2026-11-13');
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString($product->nama_produk, $csv);
        $this->assertStringContainsString(';25;', $csv);
    }
}
