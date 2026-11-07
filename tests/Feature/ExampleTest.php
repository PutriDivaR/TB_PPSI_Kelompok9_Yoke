<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_the_distributor_forecast(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('e-po.distributor.hitung-forecast'));
    }
}
