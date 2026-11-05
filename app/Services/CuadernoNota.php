<?php

namespace App\Services;

/**
 * Cálculo de la nota de un trimestre del Cuaderno Pedagógico, compartido por el cuaderno
 * del docente y la vista de notas trimestrales del director, para que siempre coincidan.
 *
 * Dimensiones: SER (3 notas, 10 pts), SABER (5 notas, 45 pts) y HACER (5 notas, 40 pts),
 * cada nota en escala 1-100 que se lleva a los puntos de su dimensión; más la AUTOEVALUACIÓN
 * sobre 5. La nota del trimestre es sobre 100.
 */
class CuadernoNota
{
    public const POINTS = ['ser' => 10, 'saber' => 45, 'hacer' => 40];

    /**
     * @param  mixed  $studentGrades  Notas de un estudiante en un trimestre, agrupadas por tipo
     *                                 (activity, exam, robot, autoeval) y por número de actividad.
     */
    public static function summarize($studentGrades): array
    {
        $score = fn (string $type, int $n) => data_get($studentGrades, "{$type}.{$n}.score", '');

        $ser = [1 => $score('activity', 1), 2 => $score('activity', 2), 3 => $score('activity', 3)];
        $saber = [1 => $score('exam', 1), 2 => $score('exam', 2), 3 => $score('exam', 3), 4 => $score('exam', 4), 5 => $score('exam', 5)];
        $hacer = [1 => $score('robot', 1), 2 => $score('robot', 2), 3 => $score('robot', 3), 4 => $score('robot', 4), 5 => $score('robot', 5)];
        $autoeval = $score('autoeval', 1);

        $promSer = self::scaleToDimension($ser, self::POINTS['ser']);
        $promSaber = self::scaleToDimension($saber, self::POINTS['saber']);
        $promHacer = self::scaleToDimension($hacer, self::POINTS['hacer']);

        $filled = array_filter([$promSer, $promSaber, $promHacer], fn ($v) => $v !== null);
        $total = count($filled) ? round(array_sum($filled), 2) : null;
        $nota = ($total !== null || is_numeric($autoeval))
            ? round(($total ?? 0) + (is_numeric($autoeval) ? $autoeval : 0), 2)
            : null;

        return [
            'ser' => $ser,
            'saber' => $saber,
            'hacer' => $hacer,
            'autoeval' => $autoeval,
            'prom_ser' => $promSer,
            'prom_saber' => $promSaber,
            'prom_hacer' => $promHacer,
            'total' => $total,
            'nota' => $nota,
        ];
    }

    /** Promedio anual: el de los trimestres que tienen nota (null si ninguno la tiene). */
    public static function annual(array $quarterNotes): ?float
    {
        $filled = array_values(array_filter($quarterNotes, fn ($v) => $v !== null));

        return $filled ? round(array_sum($filled) / count($filled), 2) : null;
    }

    private static function scaleToDimension(array $scores, int $maxPoints): ?float
    {
        $numeric = array_values(array_filter($scores, fn ($v) => is_numeric($v) && $v !== ''));
        if (empty($numeric)) {
            return null;
        }

        return round((array_sum($numeric) / count($numeric) / 100) * $maxPoints, 2);
    }
}
