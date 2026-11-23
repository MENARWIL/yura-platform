<?php

namespace Tests\Unit;

use App\Services\CuadernoNota;
use PHPUnit\Framework\TestCase;

/** Promedio anual del boletín y de Notas trimestrales: siempre entre 3; el trimestre sin notas cuenta como 0. */
class CuadernoNotaAnnualTest extends TestCase
{
    public function test_only_the_first_trimester_is_divided_by_three(): void
    {
        // El caso del boletín: solo el 1.er trimestre con 30.38.
        $this->assertSame(10.13, CuadernoNota::annual([1 => 30.38, 2 => null, 3 => null]));
    }

    public function test_a_missing_trimester_in_the_middle_counts_as_zero(): void
    {
        $this->assertSame(50.0, CuadernoNota::annual([1 => 80.0, 2 => null, 3 => 70.0]));
    }

    public function test_with_the_three_trimesters_it_is_the_usual_average(): void
    {
        $this->assertSame(90.0, CuadernoNota::annual([1 => 80.0, 2 => 90.0, 3 => 100.0]));
    }

    public function test_without_any_note_there_is_no_annual_average(): void
    {
        $this->assertNull(CuadernoNota::annual([1 => null, 2 => null, 3 => null]));
    }
}
