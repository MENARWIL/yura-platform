<?php

namespace App\Console\Commands;

use App\Models\RobotDevice;
use App\Models\Subject;
use App\Services\RobotDeviceService;
use Illuminate\Console\Command;

/**
 * Registrar o renovar la llave del robot desde la consola del servidor (la pantalla «Robot YURA» hace lo mismo).
 * Con --solo-llave imprime únicamente la llave, para pasarla por una tubería al robot sin mostrarla.
 */
class RobotLlave extends Command
{
    protected $signature = 'robot:llave
        {nombre : Nombre del robot, por ejemplo "YURA - Raspberry aula Quechua"}
        {--materia=Quechua : Materia del robot}
        {--solo-llave : Imprime solo la llave}';

    protected $description = 'Registra un robot YURA (o renueva la llave del que ya tiene ese nombre) y muestra su llave una sola vez';

    public function handle(RobotDeviceService $robots): int
    {
        $subject = Subject::where('name', $this->option('materia'))->first();
        if (! $subject) {
            $this->error('No existe la materia «' . $this->option('materia') . '».');

            return self::FAILURE;
        }

        $device = RobotDevice::where('name', $this->argument('nombre'))->first();
        if ($device) {
            $key = $robots->rotate($device);
        } else {
            [, $key] = $robots->register($this->argument('nombre'), $subject, null, null);
        }

        if ($this->option('solo-llave')) {
            $this->line($key);
        } else {
            $this->info('Llave (se muestra una sola vez): ' . $key);
        }

        return self::SUCCESS;
    }
}
