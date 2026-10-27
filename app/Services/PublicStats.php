<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\RobotActivity;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Cifras públicas de la portada: SOLO conteos agregados, nunca datos personales.
 *
 * - students: estudiantes activos y no eliminados.
 * - teachers: usuarios con rol profesor, activos y no eliminados.
 * - activities: actividades del robot completadas (de todas las gestiones).
 * - grades: notas registradas y completadas (de todas las gestiones) de estudiantes no eliminados.
 * - show: true solo si hay al menos config('yura.stats_min') estudiantes (por defecto 10),
 *   para no mostrar cifras que identifiquen a un grupo muy pequeño.
 *
 * Los conteos se guardan 10 minutos en caché; el umbral se evalúa en cada lectura,
 * así que un cambio de configuración se nota sin vaciar la caché.
 * Si una tabla o columna falla, esa cifra vale 0 y no se lanza ninguna excepción.
 */
class PublicStats
{
    public const CACHE_KEY = 'public_stats.counts';

    public const CACHE_MINUTES = 10;

    public const DEFAULT_MIN = 10;

    /**
     * @return array{students:int, teachers:int, activities:int, grades:int, show:bool}
     */
    public static function summary(): array
    {
        $counts = self::counts();

        $min = config('yura.stats_min');
        $min = is_numeric($min) && (int) $min > 0 ? (int) $min : self::DEFAULT_MIN;

        return $counts + ['show' => $counts['students'] >= $min];
    }

    /**
     * Borra la caché de conteos (útil en pruebas o tras importar muchos datos).
     */
    public static function flush(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // Sin caché disponible no hay nada que borrar.
        }
    }

    /**
     * @return array{students:int, teachers:int, activities:int, grades:int}
     */
    private static function counts(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), fn () => self::compute());
        } catch (\Throwable $e) {
            // Si la caché falla, se calcula en el momento: la portada nunca debe caerse por unas cifras.
            return self::compute();
        }
    }

    /**
     * @return array{students:int, teachers:int, activities:int, grades:int}
     */
    private static function compute(): array
    {
        return [
            'students' => self::safeCount(fn () => self::activeStudents()->count()),
            'teachers' => self::safeCount(fn () => User::ofRole('profesor')->active()->count()),
            // Sin el filtro de gestión: es el acumulado del proyecto, y así una consulta
            // con ?year_id= no puede alterar lo que queda guardado en caché.
            'activities' => self::safeCount(fn () => RobotActivity::withoutGlobalScope('academic_year')
                ->where('status', 'completed')
                ->count()),
            'grades' => self::safeCount(fn () => Grade::withoutGlobalScope('academic_year')
                ->where('status', 'completed')
                ->whereIn('student_id', Student::query()->select('id'))
                ->count()),
        ];
    }

    private static function activeStudents()
    {
        $column = Schema::hasColumn('students', 'status') ? 'status' : 'estado';

        return Student::query()->whereIn($column, ['active', 'activo']);
    }

    private static function safeCount(callable $callback): int
    {
        try {
            return max(0, (int) $callback());
        } catch (\Throwable $e) {
            Log::warning('PublicStats: no se pudo calcular una cifra pública.', ['error' => $e->getMessage()]);

            return 0;
        }
    }
}
