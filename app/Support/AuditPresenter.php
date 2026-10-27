<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Traduce los registros de auditoría a lenguaje simple para el administrador:
 * qué se hizo, en qué módulo y un detalle en una frase (con nombres, no con códigos ni identificadores).
 * Lo que no conoce lo muestra con claves legibles, sin romperse.
 */
class AuditPresenter
{
    /** Acción => [texto corto, tono]. El tono (danger/normal) solo cambia el color de la etiqueta. */
    private const ACTIONS = [
        'user.create' => ['Usuario creado', 'normal'],
        'user.update' => ['Usuario editado', 'normal'],
        'user.status' => ['Estado cambiado', 'normal'],
        'user.delete' => ['Usuario eliminado', 'danger'],
        'user.restore' => ['Usuario restaurado', 'normal'],
        'user.force_delete' => ['Eliminado definitivamente', 'danger'],
        'year.create' => ['Gestión creada', 'normal'],
        'year.current' => ['Gestión actual cambiada', 'normal'],
        'year.close' => ['Gestión cerrada', 'danger'],
        'year.reopen' => ['Gestión reabierta', 'normal'],
        'year.export' => ['Respaldo descargado', 'normal'],
        'guardian_assigned' => ['Responsable asignado', 'normal'],
        'student_info_updated' => ['Datos completados', 'normal'],
        'students.import' => ['Estudiantes importados', 'normal'],
    ];

    private const ENTITIES = [
        'user' => 'Usuarios',
        'academic_year' => 'Gestiones',
        'student' => 'Estudiantes',
    ];

    private const ROLES = [
        'admin' => 'Administrador', 'academic' => 'Académico', 'profesor' => 'Profesor',
        'padre' => 'Padre', 'madre' => 'Madre', 'tutor' => 'Tutor', 'estudiante' => 'Estudiante',
    ];

    private const STATUSES = ['active' => 'Activo', 'activo' => 'Activo', 'inactive' => 'Inactivo', 'inactivo' => 'Inactivo', 'suspended' => 'Suspendido', 'suspendido' => 'Suspendido'];

    private const FIELDS = [
        'name' => 'el nombre', 'email' => 'el correo', 'telefono' => 'el teléfono', 'role' => 'el rol', 'password' => 'la contraseña',
        'level' => 'el nivel', 'age' => 'la edad', 'gender' => 'el género',
    ];

    /** Texto corto de la acción (si no la conoce, el código tal cual). */
    public static function action(string $code): string
    {
        return self::ACTIONS[$code][0] ?? $code;
    }

    /** 'danger' para lo que elimina o cierra; 'normal' para el resto. */
    public static function tone(string $code): string
    {
        return self::ACTIONS[$code][1] ?? 'normal';
    }

    /** Nombre del rol en castellano (admin → Administrador). */
    public static function role(?string $role): string
    {
        return self::ROLES[$role ?? ''] ?? ($role ? ucfirst($role) : '—');
    }

    /** Nombre del módulo. Ignora mayúsculas ('Student' y 'student' son lo mismo). */
    public static function entity(?string $entity): string
    {
        $key = mb_strtolower((string) $entity);

        return self::ENTITIES[$key] ?? ($entity !== null && $entity !== '' ? ucfirst($entity) : '—');
    }

    /**
     * Nombres de las personas, estudiantes y gestiones que aparecen en los registros, cargados de una vez
     * para no consultar uno por uno. Incluye también a los eliminados (se pueden restaurar).
     *
     * @return array{user: array<int,string>, student: array<int,string>, academic_year: array<int,string>}
     */
    public static function names(Collection $logs): array
    {
        $ids = ['user' => [], 'student' => [], 'academic_year' => []];
        foreach ($logs as $log) {
            $entity = mb_strtolower((string) $log->entity);
            if (isset($ids[$entity]) && $log->entity_id) {
                $ids[$entity][] = (int) $log->entity_id;
            }
            if (! empty($log->details['guardian_user_id'])) {
                $ids['user'][] = (int) $log->details['guardian_user_id'];
            }
        }

        return [
            'user' => $ids['user'] ? User::withTrashed()->whereIn('id', array_unique($ids['user']))->pluck('name', 'id')->all() : [],
            'student' => $ids['student'] ? Student::withTrashed()->whereIn('id', array_unique($ids['student']))->pluck('name', 'id')->all() : [],
            'academic_year' => $ids['academic_year'] ? AcademicYear::whereIn('id', array_unique($ids['academic_year']))->pluck('name', 'id')->all() : [],
        ];
    }

    /** Una frase simple sobre lo que pasó. $names viene de names(). */
    public static function detail(AuditLog $log, array $names = []): string
    {
        $d = is_array($log->details) ? $log->details : [];
        $who = fn (string $kind, ?int $id, string $fallback) => $id && isset($names[$kind][$id]) ? $names[$kind][$id] : $fallback;
        $target = $who('user', $log->entity_id ? (int) $log->entity_id : null, 'un usuario');
        $student = $who('student', $log->entity_id ? (int) $log->entity_id : null, 'un estudiante');
        $year = isset($d['year']) ? 'Gestión ' . $d['year'] : ($names['academic_year'][$log->entity_id] ?? 'Gestión escolar');
        $fields = fn (array $list) => self::join(array_map(fn ($f) => self::FIELDS[$f] ?? $f, $list));

        return match ($log->action) {
            'user.create' => "Nuevo usuario: {$target}" . (! empty($d['role']) ? ' (' . (self::ROLES[$d['role']] ?? $d['role']) . ')' : '') . '.',
            'user.update' => empty($d['changed'])
                ? "Se guardaron los datos de {$target} sin cambios."
                : "Se cambió {$fields($d['changed'])} de {$target}.",
            'user.status' => "{$target}: de " . self::status($d['from'] ?? null) . ' a ' . self::status($d['to'] ?? null) . '.',
            'user.delete' => "Se eliminó a {$target}. Se puede restaurar.",
            'user.restore' => "Se restauró a {$target}.",
            'user.force_delete' => "Se eliminó definitivamente a {$target}" . (! empty($d['email']) ? " ({$d['email']})" : '') . '.',
            'year.create' => "Se creó la {$year}.",
            'year.current' => "La {$year} ahora es la gestión actual.",
            'year.close' => "Se cerró la {$year}." . (! empty($d['next_current']) ? " La nueva gestión actual es la {$d['next_current']}." : ''),
            'year.reopen' => "Se reabrió la {$year}.",
            'year.export' => "Respaldo en Excel de la {$year}" . (isset($d['closed']) ? ($d['closed'] ? ' (cerrada).' : ' (abierta).') : '.'),
            'student_info_updated' => empty($d['fields'])
                ? "Se revisaron los datos de {$student} sin cambios."
                : "Se completó {$fields($d['fields'])} de {$student}.",
            'guardian_assigned' => self::guardian($d, $names, $student),
            'students.import' => self::import($d),
            default => self::generic($d),
        };
    }

    private static function guardian(array $d, array $names, string $student): string
    {
        $guardian = $names['user'][(int) ($d['guardian_user_id'] ?? 0)] ?? 'Un responsable';
        $relation = self::ROLES[$d['relation_type'] ?? ''] ?? 'responsable';
        $text = "{$guardian} quedó como " . mb_strtolower($relation) . (! empty($d['is_primary']) ? ' principal' : '') . " de {$student}.";

        return $text . (! empty($d['guardian_created']) ? ' Se creó su cuenta.' : '');
    }

    private static function import(array $d): string
    {
        $parts = [];
        if (isset($d['created'])) {
            $parts[] = $d['created'] . ' ' . ((int) $d['created'] === 1 ? 'nuevo' : 'nuevos');
        }
        if (! empty($d['skipped'])) {
            $parts[] = $d['skipped'] . ' omitidos';
        }
        if (! empty($d['test_deleted'])) {
            $parts[] = $d['test_deleted'] . ' de prueba eliminados';
        }

        return 'Lista de estudiantes importada' . ($parts ? ': ' . self::join($parts) : '') . '.';
    }

    /** Para acciones que no conoce: «clave: valor» simples, sin llaves ni comillas. */
    private static function generic(array $d): string
    {
        $parts = [];
        foreach ($d as $key => $value) {
            $text = is_scalar($value) || $value === null
                ? ($value === true ? 'sí' : ($value === false ? 'no' : (string) $value))
                : implode(', ', array_map('strval', array_filter((array) $value, 'is_scalar')));
            $parts[] = str_replace('_', ' ', (string) $key) . ': ' . $text;
        }

        return $parts ? implode(' · ', $parts) : '—';
    }

    private static function status(?string $status): string
    {
        return self::STATUSES[$status ?? ''] ?? ($status ? ucfirst($status) : '—');
    }

    /** «a, b y c». */
    private static function join(array $items): string
    {
        $items = array_values($items);
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        return implode(', ', array_slice($items, 0, -1)) . ' y ' . end($items);
    }
}
