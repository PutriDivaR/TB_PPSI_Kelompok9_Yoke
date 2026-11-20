<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class KlaimSamTest extends TestCase
{
    public function test_antrean_lists_claims_oldest_first_with_summary(): void
    {
        Carbon::setTestNow('2026-11-20 10:00:00');

        $response = $this->get(route('klaim.sam.antrean'));

        $response->assertOk()
            ->assertSee('Pemeriksaan fisik wajib dilakukan langsung di gudang distributor')
            ->assertSee('PT Tirta Niaga Mandiri')
            ->assertSee(route('klaim.sam.antrean'))
            ->assertViewHas('stats', [
                'menunggu_kunjungan' => 4,
                'kunjungan_hari_ini' => 2,
                'bak_bulan_ini' => 2,
            ])
            ->assertViewHas('antrean', fn ($antrean) => $antrean->first()['kode'] === 'KLM-00016' && $antrean->first()['lama_menunggu'] === 8)
            ->assertSee(route('klaim.sam.verifikasi', 16));
    }

    public function test_verifikasi_shows_claim_rows_and_camera_capture(): void
    {
        $response = $this->get(route('klaim.sam.verifikasi', 18));

        $response->assertOk()
            ->assertSee('MRKU 4821937')
            ->assertSee('YFI Jus Apel 330ml')
            ->assertSee('capture="environment"', false)
            ->assertSee('Lanjut ke Pengesahan BAK')
            ->assertSee(route('klaim.sam.pengesahan', 18), false);
    }

    public function test_pengesahan_prefills_bak_with_verified_amounts_and_signer(): void
    {
        $response = $this->get(route('klaim.sam.pengesahan', 18));

        $response->assertOk()
            ->assertSee('BAK-00027')
            ->assertSee('Farhan Fitrahadi')
            ->assertSee('<canvas', false)
            ->assertViewHas('bak', fn ($bak) => $bak['per_penanggung']->all() === ['Perusahaan' => 62]);
    }

    public function test_pengesahan_unknown_claim_returns_not_found(): void
    {
        $this->get(route('klaim.sam.pengesahan', 99))->assertNotFound();
    }

    public function test_verifikasi_unknown_claim_returns_not_found(): void
    {
        $this->get(route('klaim.sam.verifikasi', 99))->assertNotFound();
    }
}
