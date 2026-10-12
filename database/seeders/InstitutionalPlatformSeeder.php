<?php

namespace Database\Seeders;

use App\Models\EducationalService;
use App\Models\OfficialDocument;
use Illuminate\Database\Seeder;

class InstitutionalPlatformSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Servicios Educativos
        $services = [
            [
                'name' => 'Cuaderno Pedagógico Digital',
                'subsystem' => 'regular',
                'description' => 'Sistema oficial para el registro trimestral de evaluaciones, ser-saber-hacer-decidir y asistencia.',
                'icon' => 'fa-book-open',
                'action_url' => '/grades',
                'target_audience' => 'Docentes de Educación Regular',
                'sort_order' => 1,
            ],
            [
                'name' => 'Portal de Acompañamiento Familiar',
                'subsystem' => 'regular',
                'description' => 'Consulta en tiempo real del progreso pedagógico, asistencia y rendimiento de los estudiantes.',
                'icon' => 'fa-users',
                'action_url' => '/dashboard',
                'target_audience' => 'Padres, Madres y Tutores',
                'sort_order' => 2,
            ],
            [
                'name' => 'Módulo de Innovación Lingüística (Robot YURA)',
                'subsystem' => 'regular',
                'description' => 'Práctica pedagógica interactiva con IA ASR para el fortalecimiento de la lengua Quechua originaria.',
                'icon' => 'fa-robot',
                'action_url' => '/robot-activities',
                'target_audience' => 'Docentes y Estudiantes de Primaria',
                'sort_order' => 3,
            ],
            [
                'name' => 'Educación Alternativa y Permanente',
                'subsystem' => 'alternativa_especial',
                'description' => 'Programas de alfabetización, formación técnica de adultos y educación para personas con discapacidad.',
                'icon' => 'fa-hands-holding-child',
                'action_url' => '#',
                'target_audience' => 'Comunidad y Jóvenes-Adultos',
                'sort_order' => 4,
            ],
            [
                'name' => 'Formación Superior Técnica y Tecnológica',
                'subsystem' => 'superior',
                'description' => 'Orientación vocacional e información académica sobre carreras técnicas e institutos superiores.',
                'icon' => 'fa-graduation-cap',
                'action_url' => '#',
                'target_audience' => 'Bachilleres y Postulantes',
                'sort_order' => 5,
            ],
        ];

        foreach ($services as $srv) {
            EducationalService::updateOrCreate(['name' => $srv['name']], $srv);
        }

        // 2. Documentación Oficial y Normativa
        $documents = [
            [
                'title' => 'Resolución Administrativa 001/2026 - Normas Generales de Gestión Educativa',
                'code' => 'RA-001/2026',
                'category' => 'resolucion',
                'summary' => 'Directrices generales para la planificación, organización, ejecución y evaluación de la gestión educativa anual.',
                'year' => 2026,
                'file_path' => null,
                'published_at' => '2026-02-01',
                'active' => true,
            ],
            [
                'title' => 'Convocatoria Pública 004/2026 - Feria de Innovación Educativa y Robótica',
                'code' => 'CONV-004/2026',
                'category' => 'convocatoria',
                'summary' => 'Bases de participación para proyectos de apoyo tecnológico en lenguas originarias y robótica aplicada al aula.',
                'year' => 2026,
                'file_path' => null,
                'published_at' => '2026-03-15',
                'active' => true,
            ],
            [
                'title' => 'Circular 015/2026 - Calendario Oficial del Segundo Trimestre Escolar',
                'code' => 'CIRC-015/2026',
                'category' => 'circular',
                'summary' => 'Cronograma de cierre de evaluaciones pedagógicas, registro de asistencia y entrega de reportes a familias.',
                'year' => 2026,
                'file_path' => null,
                'published_at' => '2026-06-10',
                'active' => true,
            ],
        ];

        foreach ($documents as $doc) {
            OfficialDocument::updateOrCreate(['code' => $doc['code']], $doc);
        }
    }
}
