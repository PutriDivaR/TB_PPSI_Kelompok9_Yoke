<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Wilayah;
use Tests\Concerns\MembangunSkemaYoke;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase
{
    use MembangunSkemaYoke;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bangunSkemaYoke();
    }

    protected function tearDown(): void
    {
        $this->bongkarSkemaYoke();
        parent::tearDown();
    }

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_internal_pages_redirect_guests_to_login(): void
    {
        $this->get(route('e-po.distributor.hitung-forecast'))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_open_another_role_dashboard(): void
    {
        $user = $this->buatPengguna('Distributor');

        $this->actingAs($user)->get(route('admin.ringkasan'))->assertForbidden();
    }

    public function test_distributor_pages_use_the_authenticated_accounts_company(): void
    {
        $wilayah = Wilayah::factory()->create();
        $assignedDistributor = Distributor::factory()->create([
            'nama' => 'PT Akun Terdaftar',
            'id_wilayah' => $wilayah->id_wilayah,
        ]);
        $otherDistributor = Distributor::factory()->create([
            'nama' => 'PT Perusahaan Lain',
            'id_wilayah' => $wilayah->id_wilayah,
        ]);
        $user = Pengguna::factory()->create([
            'id_peran' => Peran::factory()->create(['nama_peran' => 'Distributor'])->id_peran,
            'id_distributor' => $assignedDistributor->id_distributor,
        ]);

        $this->actingAs($user)
            ->withSession(['active_distributor_id' => $otherDistributor->id_distributor])
            ->get(route('e-po.distributor.hitung-forecast'))
            ->assertOk()
            ->assertSee('PT Akun Terdaftar')
            ->assertDontSee('PT Perusahaan Lain')
            ->assertDontSee('Perusahaan simulasi');
    }

    public function test_authenticated_user_is_redirected_to_the_dashboard_for_their_role(): void
    {
        $user = $this->buatPengguna('Distributor');

        $this->actingAs($user)->get(route('login'))
            ->assertRedirect(route('e-po.distributor.hitung-forecast'));
    }

    public function test_simulation_routes_are_no_longer_available(): void
    {
        $this->get('/distributor')->assertNotFound();
        $this->get('/logistik')->assertNotFound();
        $this->get('/sam')->assertNotFound();
        $this->get('/simulasi/admin')->assertNotFound();
    }

    private function buatPengguna(string $namaPeran): Pengguna
    {
        return Pengguna::factory()->create([
            'id_peran' => Peran::factory()->create(['nama_peran' => $namaPeran])->id_peran,
        ]);
    }
}
