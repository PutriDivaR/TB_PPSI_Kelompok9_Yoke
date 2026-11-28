<?php

namespace Tests\Feature;

use Tests\TestCase;

class MonitoringPengirimanTest extends TestCase
{
    public function test_monitoring_dashboard_renders_for_distributor(): void
    {
        $response = $this->get('/monitoring?role=distributor');

        $response->assertOk()
            ->assertSee('Monitoring Pengiriman')
            ->assertSee('Ringkasan Status Pengiriman')
            ->assertSee('Daftar Kontainer Aktif');
    }

    public function test_monitoring_dashboard_renders_for_logistik(): void
    {
        $response = $this->get('/monitoring?role=logistik');

        $response->assertOk()
            ->assertSee('Monitoring Pengiriman')
            ->assertSee('Update Status Kontainer')
            ->assertSee('Input Pengiriman Baru')
            ->assertSee('Input Pengiriman Kontainer Baru');
    }

    public function test_logistik_can_store_new_shipment(): void
    {
        $uniqueContainer = 'CNTR-TEST-'.uniqid();

        $response = $this->post('/monitoring/store', [
            'id_po' => 1,
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
        $response = $this->get('/monitoring?role=sam');

        $response->assertOk()
            ->assertSee('Monitoring Pengiriman')
            ->assertSee('Lihat Detail');
    }

    public function test_monitoring_detail_renders_successfully(): void
    {
        $response = $this->get('/monitoring/detail/1?role=distributor');

        $response->assertOk()
            ->assertSee('Detail Pengiriman Kontainer')
            ->assertSee('Timeline Milestone Perjalanan')
            ->assertSee('Daftar Barang dalam Kontainer');
    }

    public function test_monitoring_update_page_renders_for_logistik(): void
    {
        $response = $this->get('/monitoring/update/1?role=logistik');

        $response->assertOk()
            ->assertSee('Pembaruan Status Pengiriman')
            ->assertSee('Progres Pengiriman')
            ->assertSee('Simpan Pembaruan Status');
    }

    public function test_monitoring_konfirmasi_page_renders_for_distributor(): void
    {
        $response = $this->get('/monitoring/konfirmasi/1?role=distributor');

        $response->assertOk()
            ->assertSee('Konfirmasi Penerimaan Barang')
            ->assertSee('Kondisi Penerimaan Barang');
    }
}
