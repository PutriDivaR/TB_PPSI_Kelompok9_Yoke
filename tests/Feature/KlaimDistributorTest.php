<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class KlaimDistributorTest extends TestCase
{
    public function test_beranda_shows_claim_history_and_summary(): void
    {
        Carbon::setTestNow('2026-11-20 10:00:00');

        $response = $this->get(route('klaim.distributor.beranda'));

        $response->assertOk()
            ->assertSee('Modul Manajemen Klaim & Retur')
            ->assertSee('KLM-00018')
            ->assertSee('MRKU 4821937')
            ->assertViewHas('stats', [
                'menunggu_verifikasi' => 1,
                'sisa_hari_sortir' => 5,
                'selesai_bulan_ini' => 1,
                'karton_tahun_fiskal' => 370,
            ])
            ->assertSee(route('klaim.distributor.ajukan'))
            ->assertSee(route('klaim.distributor.detail', 18));
    }

    public function test_detail_marks_the_current_stage_and_hides_missing_bak(): void
    {
        $response = $this->get(route('klaim.distributor.detail', 18));

        $response->assertOk()
            ->assertSee('KLM-00018')
            ->assertDontSee('BAK-')
            ->assertViewHas('tahap', fn ($tahap) => $tahap->pluck('status')->all() === ['lewat', 'lewat', 'berjalan', 'belum', 'belum']);
    }

    public function test_detail_shows_signed_bak_when_available(): void
    {
        $response = $this->get(route('klaim.distributor.detail', 17));

        $response->assertOk()
            ->assertSee('BAK-00009')
            ->assertSee('Farhan Fitrahadi')
            ->assertViewHas('tahap', fn ($tahap) => $tahap->last()['status'] === 'berjalan');
    }

    public function test_detail_without_sample_data_returns_not_found(): void
    {
        $this->get(route('klaim.distributor.detail', 14))->assertNotFound();
    }

    public function test_ajukan_shows_received_containers_with_invoice_difference(): void
    {
        $response = $this->get(route('klaim.distributor.ajukan'));

        $response->assertOk()
            ->assertSee('SEGU 2240718')
            ->assertSee('Kirim Klaim')
            ->assertViewHas('kedatangan', fn ($kedatangan) => $kedatangan->firstWhere('nomor_kontainer', 'SEGU 2240718')['selisih'] === -24);
    }
}
