<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\MilestonePengiriman;
use App\Models\Pengguna;
use App\Models\Pengiriman;
use App\Models\Peran;
use App\Models\PurchaseOrder;
use App\Models\Wilayah;
use Tests\Concerns\MembangunSkemaYoke;
use Tests\TestCase;

class MonitoringPengirimanTest extends TestCase
{
    use MembangunSkemaYoke;

    protected Wilayah $wilayah;
    protected Distributor $distributor;
    protected Pengguna $userDistributor;
    protected Pengguna $userLogistik;
    protected Pengguna $userSam;
    protected PurchaseOrder $po;
    protected Pengiriman $pengiriman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->bangunSkemaYoke();

        $this->wilayah = Wilayah::factory()->create(['nama_wilayah' => 'Sumatera Barat']);
        $this->distributor = Distributor::factory()->create([
            'nama' => 'PT Minang Niaga Jaya',
            'id_wilayah' => $this->wilayah->id_wilayah,
        ]);

        $peranDistributor = Peran::firstOrCreate(['nama_peran' => 'Distributor']);
        $peranLogistik = Peran::firstOrCreate(['nama_peran' => 'Logistik Pusat']);
        $peranSam = Peran::firstOrCreate(['nama_peran' => 'SAM']);

        $this->userDistributor = Pengguna::factory()->create([
            'nama' => 'Fajar Distributor',
            'email' => 'distributor@yoke.test',
            'id_peran' => $peranDistributor->id_peran,
            'id_distributor' => $this->distributor->id_distributor,
            'status_aktif' => true,
        ]);

        $this->userLogistik = Pengguna::factory()->create([
            'nama' => 'Budi Logistik',
            'email' => 'logistik@yoke.test',
            'id_peran' => $peranLogistik->id_peran,
            'status_aktif' => true,
        ]);

        $this->userSam = Pengguna::factory()->create([
            'nama' => 'Dedi SAM',
            'email' => 'sam@yoke.test',
            'id_peran' => $peranSam->id_peran,
            'status_aktif' => true,
        ]);

        $forecast = Forecast::factory()->create([
            'id_distributor' => $this->distributor->id_distributor,
            'status' => 'Disetujui',
        ]);

        $this->po = PurchaseOrder::factory()->create([
            'id_forecast' => $forecast->id_forecast,
            'nomor_po' => 'PO-YFI-2026-0041',
            'status' => 'Disetujui',
        ]);

        $this->pengiriman = Pengiriman::factory()->create([
            'id_po' => $this->po->id_po,
            'nomor_kontainer' => 'CNTR-2026-001',
            'status' => 'Dalam Perjalanan',
            'eta_awal' => '2026-12-01',
            'eta_terkini' => '2026-12-01',
        ]);

        MilestonePengiriman::create([
            'id_pengiriman' => $this->pengiriman->id_pengiriman,
            'urutan' => 1,
            'nama_tahap' => 'Booking Dikonfirmasi',
            'tanggal_pembaruan' => now(),
            'keterangan' => 'Booking kontainer terkonfirmasi',
        ]);
    }

    protected function tearDown(): void
    {
        $this->bongkarSkemaYoke();
        parent::tearDown();
    }

    public function test_monitoring_dashboard_renders_for_distributor(): void
    {
        $response = $this->actingAs($this->userDistributor)->get('/monitoring');

        $response->assertOk()
            ->assertSee('Monitoring Pengiriman')
            ->assertSee('Ringkasan Status Pengiriman')
            ->assertSee('Daftar Kontainer Aktif');
    }

    public function test_monitoring_dashboard_renders_for_logistik(): void
    {
        $response = $this->actingAs($this->userLogistik)->get('/monitoring');

        $response->assertOk()
            ->assertSee('Monitoring Pengiriman')
            ->assertSee('Update Status Kontainer')
            ->assertSee('Input Pengiriman Baru')
            ->assertSee('Input Pengiriman Kontainer Baru');
    }

    public function test_logistik_can_store_new_shipment(): void
    {
        $uniqueContainer = 'CNTR-TEST-' . uniqid();

        $response = $this->actingAs($this->userLogistik)->post('/monitoring/store', [
            'id_po' => $this->po->id_po,
            'nomor_kontainer' => $uniqueContainer,
            'nama_vessel' => 'MV Test Vessel V.1',
            'eta_awal' => '2026-12-05',
            'eta_terkini' => '2026-12-05',
            'status' => 'Menunggu Muat',
            'keterangan' => 'Test pendaftaran pengiriman baru',
        ]);

        $response->assertRedirect('/monitoring?role=logistik');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pengiriman', [
            'nomor_kontainer' => $uniqueContainer,
            'status' => 'Menunggu Muat',
        ]);

        $this->assertDatabaseHas('milestone_pengiriman', [
            'nama_tahap' => 'Booking Dikonfirmasi',
            'urutan' => 1,
        ]);
    }

    public function test_monitoring_dashboard_renders_for_sam(): void
    {
        $response = $this->actingAs($this->userSam)->get('/monitoring');

        $response->assertOk()
            ->assertSee('Monitoring Pengiriman')
            ->assertSee('Lihat Detail');
    }

    public function test_monitoring_detail_renders_successfully(): void
    {
        $response = $this->actingAs($this->userDistributor)->get('/monitoring/detail/' . $this->pengiriman->id_pengiriman);

        $response->assertOk()
            ->assertSee('Detail Pengiriman Kontainer')
            ->assertSee('Timeline Milestone Perjalanan')
            ->assertSee('Daftar Barang dalam Kontainer');
    }

    public function test_monitoring_update_page_renders_for_logistik(): void
    {
        $response = $this->actingAs($this->userLogistik)->get('/monitoring/update/' . $this->pengiriman->id_pengiriman);

        $response->assertOk()
            ->assertSee('Pembaruan Status Pengiriman')
            ->assertSee('Progres Pengiriman')
            ->assertSee('Simpan Pembaruan Status');
    }

    public function test_monitoring_konfirmasi_page_renders_for_distributor(): void
    {
        $response = $this->actingAs($this->userDistributor)->get('/monitoring/konfirmasi/' . $this->pengiriman->id_pengiriman);

        $response->assertOk()
            ->assertSee('Konfirmasi Penerimaan Barang')
            ->assertSee('Kondisi Penerimaan Barang');
    }
}
