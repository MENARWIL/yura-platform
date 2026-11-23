<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/** Mensajes de validación en castellano: el año de gestión avisa igual que la web y el servidor ya no responde en inglés. */
class SpanishValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_the_year_of_a_new_gestion_gets_the_same_messages_as_the_web(): void
    {
        $admin = $this->admin();
        $cases = [
            '' => 'Este campo es obligatorio.',
            'abc' => 'Este campo debe ser un número entero.',
            '2026.5' => 'Este campo debe ser un número entero.',
            '1999' => 'Escribe un año entre 2000 y 2100.',
            '2101' => 'Escribe un año entre 2000 y 2100.',
        ];

        foreach ($cases as $year => $message) {
            $this->actingAs($admin)->from('/admin/years')->post('/admin/years', ['year' => $year])
                ->assertRedirect('/admin/years')
                ->assertSessionHasErrors(['year' => $message]);
        }
    }

    public function test_the_year_form_is_checked_by_the_web_and_not_by_the_browser_bubble(): void
    {
        $this->actingAs($this->admin())->get(route('admin.years.index'))
            ->assertOk()
            ->assertSee('class="form-inline" novalidate', false)
            ->assertSee('data-rule="year"', false)
            ->assertSee('js/user-form-validation.js', false);
    }

    public function test_the_wrong_year_is_kept_in_the_field_with_its_message_below(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->from('/admin/years')->post('/admin/years', ['year' => '1999']);

        $this->actingAs($admin)->get('/admin/years')
            ->assertSee('value="1999"', false)
            ->assertSee('Escribe un año entre 2000 y 2100.');
    }

    public function test_other_screens_answer_in_spanish_instead_of_english(): void
    {
        Course::create(['name' => 'Sexto de Primaria', 'nivel_educativo' => array_key_first(Course::NIVELES)]);

        $response = $this->actingAs($this->admin())->from('/courses/create')
            ->post('/courses', ['name' => 'Sexto de Primaria', 'nivel_educativo' => 'otro']);

        $response->assertSessionHasErrors([
            'name' => 'Este valor ya está registrado.',
            'nivel_educativo' => 'El valor seleccionado en nivel educativo no es válido.',
        ]);
        $this->actingAs($this->admin())->from('/courses/create')->post('/courses', [])
            ->assertSessionHasErrors(['name' => 'El campo nombre es obligatorio.']);
    }

    public function test_the_app_api_answers_in_spanish_including_the_summary(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El campo correo electrónico es obligatorio. (y 1 error más)')
            ->assertJsonPath('errors.password.0', 'El campo contraseña es obligatorio.');
    }

    public function test_pagination_labels_are_in_spanish(): void
    {
        $html = (new LengthAwarePaginator(range(1, 30), 30, 10, 1))->links()->toHtml();

        $this->assertStringContainsString('Siguiente', $html);
        $this->assertStringNotContainsString('Next', $html);
    }
}
