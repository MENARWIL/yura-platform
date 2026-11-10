<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TemporaryPasswordService;
use App\Services\UserAdminException;
use Illuminate\Support\Facades\Auth;

/**
 * Genera una contraseña temporal y muestra el botón para enviarla por WhatsApp.
 * La clave se muestra una sola vez en esta respuesta: no se guarda en la sesión ni en la base.
 */
class TemporaryPasswordController extends Controller
{
    public function __construct(private readonly TemporaryPasswordService $service)
    {
    }

    public function store(User $user)
    {
        try {
            $result = $this->service->issue(Auth::user(), $user);
        } catch (UserAdminException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()
            ->view('users.temporary-password', $result)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache');
    }
}
