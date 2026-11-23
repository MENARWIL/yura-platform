<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Ver contraseña: todo campo de contraseña de las pantallas internas lleva el ojito (yura-ui.js). */
class PasswordToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_internal_screen_with_a_password_field_loads_the_toggle_script(): void
    {
        $screens = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_contains($file->getContents(), 'type="password"'))
            ->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
            ->reject(fn ($path) => $path === 'auth/login.blade.php') // pantalla autónoma con su propio ojito
            ->values();

        $this->assertNotEmpty($screens);
        foreach ($screens as $path) {
            $this->assertStringContainsString("@extends('layouts.app')", File::get(resource_path("views/{$path}")), "{$path} no carga yura-ui.js");
        }
        $this->assertStringContainsString("asset('js/yura-ui.js')", File::get(resource_path('views/layouts/app.blade.php')));
        $this->assertStringContainsString('querySelectorAll(\'input[type="password"]\').forEach(initPasswordToggle)', File::get(public_path('js/yura-ui.js')));
    }

    public function test_the_register_tutor_screen_loads_the_toggle_script(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);

        $this->actingAs($admin)->get(route('tutors.create'))
            ->assertOk()
            ->assertSee('type="password"', false)
            ->assertSee('js/yura-ui.js', false);
    }

    public function test_the_login_keeps_its_own_toggle_and_does_not_get_a_second_one(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="pw-toggle"', false)
            ->assertDontSee('js/yura-ui.js', false);
    }
}
