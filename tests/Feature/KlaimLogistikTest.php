<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class KlaimLogistikTest extends TestCase
{
    public function test_proses_shows_bak_inbox_details_and_summary(): void
    {
        Carbon::setTestNow('2026-11-20 10:00:00');

        $response = $this->get(route('klaim.logistik.proses'));

        $response->assertOk()
            ->assertSee('BAK-00009')
            ->assertSee('KLM-00017')
            ->assertSee('Penyesuaian Catatan Kewajiban')
            ->assertSee(route('klaim.logistik.proses'))
            ->assertViewHas('stats', [
                'belum_diproses' => 3,
                'selesai_bulan_ini' => 2,
                'karton_tahun_fiskal' => 446,
            ]);
    }
}
