<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_displays_operational_statistics_and_navigation(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Total Personel Aktif')
            ->assertSee('Tugas Berjalan')
            ->assertSee('Komplain Aktif')
            ->assertSee('Peringatan Stok Minimum')
            ->assertSee('Monitoring Kebersihan')
            ->assertSee('Pengaturan Sistem');
    }
}
