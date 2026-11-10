<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Cambio obligatorio después de entrar con una contraseña temporal.
 * Mientras no la cambie, EnsurePasswordIsChanged no deja usar el resto del panel.
 */
class PasswordChangeController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        if (! $user->mustChangePassword()) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password', ['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.required' => 'Escribe tu nueva contraseña.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ], [
            'password' => 'nueva contraseña',
        ]);

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Elige una contraseña distinta de la temporal.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'password_must_change' => false,
            'temporary_password_expires_at' => null,
        ])->save();

        $request->session()->regenerate();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.password_changed',
            'entity' => 'user',
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'details' => ['after' => 'temporary_password'],
            'status' => 'success',
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Listo, tu contraseña quedó cambiada.');
    }
}
