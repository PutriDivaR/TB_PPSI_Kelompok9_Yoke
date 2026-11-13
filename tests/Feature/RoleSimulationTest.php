<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleSimulationTest extends TestCase
{
    public function test_distributor_simulation_renders_the_distributor_page(): void
    {
        $response = $this->get('/distributor');

        $response->assertOk()
            ->assertSee('PT Minang Niaga Jaya')
            ->assertSessionHas('mock_role', 'distributor');
    }

    public function test_logistik_simulation_renders_the_logistik_page(): void
    {
        $response = $this->get('/logistik');

        $response->assertOk()
            ->assertSeeText('Antrean Pengajuan Forecast')
            ->assertSessionHas('mock_role', 'logistik');
    }

    public function test_sam_simulation_renders_a_placeholder_page(): void
    {
        $response = $this->get('/sam');

        $response->assertOk()
            ->assertSee('Sales Area Manager')
            ->assertSessionHas('mock_role', 'sam');
    }

    public function test_admin_simulation_opens_the_admin_portal(): void
    {
        $this->get('/simulasi/admin')
            ->assertRedirect(route('admin.ringkasan'))
            ->assertSessionHas('mock_role', 'admin');

        $this->get('/admin/ringkasan')
            ->assertOk()
            ->assertSee('Portal Admin');
    }
}
