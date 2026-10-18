<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserAdminException;
use App\Services\UserAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403, 'No autorizado.');

        // Solo los campos necesarios: nunca documento de identidad, foto ni tokens.
        $users = User::orderBy('name')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'status' => $u->status,
            'telefono' => $u->phone ?? $u->telefono,
        ]);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403, 'No autorizado.');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'rol' => ['required', 'in:' . implode(',', UserAdminService::ROLES)],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $user = app(UserAdminService::class)->create($request->user(), [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'telefono' => $request->telefono,
            'estado' => 'activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado con éxito',
            'data' => $user
        ], 201);
    }

    public function toggleStatus(Request $request, User $user)
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403, 'No autorizado.');

        try {
            app(UserAdminService::class)->toggleStatus($request->user(), $user);
        } catch (UserAdminException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
            'data' => $user
        ]);
    }
}
