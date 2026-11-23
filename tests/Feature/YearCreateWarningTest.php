<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Crear una gestión con la actual abierta: la web avisa que la nueva no será la actual (ventana de YURA). */
class YearCreateWarningTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_the_create_form_carries_the_open_current_year_for_the_warning(): void
    {
        AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);

        $this->actingAs($this->admin())->get(route('admin.years.index'))
            ->assertOk()
            ->assertSee('id="year-create-form"', false)
            ->assertSee('data-current-year="2026"', false)
            ->assertSee('data-existing-years="2025,2026"', false)
            ->assertSee('Crear de todos modos');
    }

    public function test_without_an_open_current_year_there_is_no_warning(): void
    {
        AcademicYear::current()->forceFill(['closed_at' => now()])->save();

        $this->actingAs($this->admin())->get(route('admin.years.index'))
            ->assertOk()
            ->assertDontSee('data-current-year=', false);
    }

    public function test_the_warning_only_asks_the_server_still_creates_the_year_and_keeps_the_current_one(): void
    {
        $this->actingAs($this->admin())->post('/admin/years', ['year' => 2027])->assertRedirect('/admin/years');

        $this->assertTrue(AcademicYear::where('year', 2027)->where('is_current', false)->exists());
        $this->assertSame(2026, AcademicYear::current()->year);
    }
}
