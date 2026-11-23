<?php

namespace App\Http\Controllers;

use App\Models\RobotDevice;
use App\Models\Subject;
use App\Models\User;
use App\Services\RobotDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pantalla «Robot YURA» (contrato v1.3): registrar el robot y generar, renovar o revocar su llave de API.
 * La ruta ya exige el rol admin (administrador, director y Panel Administrativo); este último solo consulta.
 */
class RobotDeviceController extends Controller
{
    public function __construct(private readonly RobotDeviceService $robots)
    {
    }

    public function index()
    {
        $devices = RobotDevice::with(['subject', 'teacher', 'creator'])->orderByDesc('id')->get();
        $subjects = Subject::where('active', true)->orderBy('name')->get();
        $teachers = User::where('role', 'profesor')->orderBy('name')->get();

        return view('admin.robot.index', compact('devices', 'subjects', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ], [], ['name' => 'nombre', 'subject_id' => 'materia', 'teacher_user_id' => 'docente responsable']);

        $teacher = isset($data['teacher_user_id']) ? User::where('role', 'profesor')->find($data['teacher_user_id']) : null;
        [$device, $key] = $this->robots->register(
            $data['name'], Subject::findOrFail($data['subject_id']), $teacher, $request->user(), $request
        );

        return $this->withKey($device, $key, 'Robot registrado. Copia la llave ahora: no se vuelve a mostrar.');
    }

    public function rotate(Request $request, RobotDevice $device): RedirectResponse
    {
        $this->authorizeManage($request);

        return $this->withKey($device, $this->robots->rotate($device, $request),
            'Llave renovada. La anterior dejó de funcionar; copia la nueva ahora, no se vuelve a mostrar.');
    }

    public function revoke(Request $request, RobotDevice $device): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->robots->revoke($device, $request);

        return redirect()->route('admin.robot.index')
            ->with('success', "Llave de «{$device->name}» revocada: el robot ya no puede conectarse.");
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->canManageRobot(), 403, 'Solo el administrador o el director pueden gestionar la llave del robot.');
    }

    /** La llave viaja una sola vez, en la sesión, hasta que la pantalla se muestra. */
    private function withKey(RobotDevice $device, string $key, string $message): RedirectResponse
    {
        return redirect()->route('admin.robot.index')
            ->with('success', $message)
            ->with('robot_key', $key)
            ->with('robot_key_name', $device->name);
    }
}
