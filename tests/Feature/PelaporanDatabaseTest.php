<?php

namespace Tests\Feature;

use App\Models\DetailForecast;
use App\Models\DetailPenerimaan;
use App\Models\DetailPengiriman;
use App\Models\DetailPo;
use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\PenerimaanBarang;
use App\Models\Pengiriman;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\SellIn;
use App\Models\SellOut;
use App\Models\StokGudang;
use App\Services\PelaporanService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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

    private function distributorSession(Distributor $distributor): array
    {
        return ['mock_role' => 'distributor', 'active_distributor_id' => $distributor->getKey()];
    }

    public function test_report_shows_empty_database_without_using_mock_values(): void
    {
        $this->get('/pelaporan')->assertOk()->assertSee('Belum ada data transaksi di MySQL')->assertDontSee('9.600');
    }

    public function test_sell_out_is_persisted_and_visible_after_a_new_request(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $payload = ['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'kanal' => 'Retail', 'jumlah' => 25, 'tanggal' => '2026-11-12'];
        $this->withSession($this->distributorSession($company))->post('/pelaporan/sell-out', $payload)->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('sell_out', array_diff_key($payload, ['tanggal' => true]));
        $this->assertSame($payload['tanggal'], SellOut::query()->sole()->tanggal->format('Y-m-d'));
        $this->get('/pelaporan?mulai=2026-11-01&selesai=2026-11-13')->assertOk()->assertSee($product->nama_produk)->assertSee('25');
    }

    public function test_negative_sell_out_is_rejected_without_persistence(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $this->withSession($this->distributorSession($company))->post('/pelaporan/sell-out', [
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'kanal' => 'Retail', 'jumlah' => -1, 'tanggal' => '2026-11-12',
        ])->assertSessionHasErrors('jumlah');
        $this->assertDatabaseCount('sell_out', 0);
    }

    public function test_saleable_stock_above_physical_stock_is_rejected(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $this->withSession($this->distributorSession($company))->post('/pelaporan/stok', [
            'id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-12', 'stok_fisik' => 100, 'stok_layak_jual' => 101,
        ])->assertSessionHasErrors('stok_layak_jual');
        $this->assertDatabaseCount('stok_gudang', 0);
    }

    public function test_stock_snapshot_is_updated_without_duplicate_rows(): void
    {
        $company = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $payload = ['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'periode' => '2026-11-12', 'stok_fisik' => 100, 'stok_layak_jual' => 95];
        $this->withSession($this->distributorSession($company))->post('/pelaporan/stok', $payload)->assertRedirect();
        $payload['stok_fisik'] = 99;
        $payload['stok_layak_jual'] = 94;
        $this->post('/pelaporan/stok', $payload)->assertRedirect();
        $this->assertDatabaseCount('stok_gudang', 1);
        $this->assertDatabaseHas('stok_gudang', $payload);
    }

    public function test_distributor_cannot_read_or_write_another_company(): void
    {
        $company = Distributor::factory()->create();
        $other = Distributor::factory()->create();
        $product = Produk::factory()->create();
        $this->withSession($this->distributorSession($company))->get('/pelaporan?id_distributor='.$other->getKey())->assertForbidden();
        $this->post('/pelaporan/sell-out', [
            'id_distributor' => $other->getKey(), 'id_produk' => $product->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 5, 'kanal' => 'Retail',
        ])->assertForbidden();
        $this->assertDatabaseCount('sell_out', 0);
    }

    public function test_logistik_can_read_reports_but_cannot_submit_distributor_sales(): void
    {
        $this->withSession(['mock_role' => 'logistik'])->get('/pelaporan')->assertOk();
        $this->post('/pelaporan/sell-out', [])->assertForbidden();
    }

    public function test_production_environment_cannot_use_simulated_write_access(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->app->instance('env', 'production');
        $this->withSession(['mock_role' => 'distributor'])->post('/pelaporan/sell-out', [])->assertForbidden();
        $this->get('/pelaporan')->assertForbidden();
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

    public function test_export_reads_real_rows_and_escapes_spreadsheet_formulas(): void
    {
        $company = Distributor::factory()->create(['nama' => '=1+1']);
        $product = Produk::factory()->create();
        SellOut::factory()->create(['id_distributor' => $company->getKey(), 'id_produk' => $product->getKey(), 'tanggal' => '2026-11-12', 'jumlah' => 25]);
        $response = $this->withSession($this->distributorSession($company))->get('/pelaporan/ekspor?mulai=2026-11-01&selesai=2026-11-13');
        $response->assertDownload('laporan-yoke-2026-11-01-2026-11-13.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString($product->nama_produk, $csv);
        $this->assertStringContainsString(';25;', $csv);
    }
}
