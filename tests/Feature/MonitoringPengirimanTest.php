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
            ->assertSee('Update Status Kontainer');
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
