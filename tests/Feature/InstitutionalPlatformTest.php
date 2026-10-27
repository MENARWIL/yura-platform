<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_loads_home(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('YURA PLATFORM');
        $response->assertSee('Cómo funciona, en tres pasos');
        $response->assertDontSee('Trámites');
    }

    public function test_public_services_page_loads(): void
    {
        $response = $this->get('/services');
        $response->assertStatus(200);
        $response->assertSee('Servicios Educativos y Herramientas Digitales');
    }

    public function test_public_documents_page_loads(): void
    {
        $response = $this->get('/documents');
        $response->assertStatus(200);
        $response->assertSee('Repositorio Documental Institucional');
    }

    public function test_public_search_finds_results(): void
    {
        $response = $this->get('/search?q=Legalización');
        $response->assertStatus(200);
        $response->assertSee('Resultados para:');
    }

    public function test_original_fair_page_is_still_available(): void
    {
        $this->get('/feria')->assertOk();
    }

    public function test_procedures_module_is_removed(): void
    {
        $this->get('/procedures')->assertNotFound();
        $this->get('/procedures/track')->assertNotFound();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'activo']);
        $this->actingAs($admin)->get('/admin/procedures')->assertNotFound();
    }

    public function test_only_admin_can_access_audit_log(): void
    {
        $academic = User::factory()->create(['role' => 'academic', 'status' => 'activo']);
        $this->actingAs($academic)->get('/admin/audit')->assertRedirect('/');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'activo']);
        $this->actingAs($admin)->get('/admin/audit')->assertOk();
    }
}
