<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

/**
 * Contraseñas temporales que se envían por WhatsApp (enlace wa.me: quien la genera pulsa «Enviar»).
 *
 * Solo la Dirección, el administrador principal y la Secretaría pueden generarlas. La clave vence
 * a las 24 horas, cierra las sesiones abiertas del usuario y lo obliga a cambiarla al entrar.
 */
class TemporaryPasswordService
{
    public const VALID_HOURS = 24;

    /** Sin letras ni números que se confunden al leerlos en el celular (0/O, 1/l/I). */
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private readonly WhatsAppClient $whatsapp)
    {
    }

    /**
     * Genera la clave, la envía desde el número del colegio (si la API está configurada) y,
     * si no se pudo, devuelve el enlace wa.me para enviarla a mano.
     *
     * @return array{user: User, password: string, expires_at: Carbon, phone: string, whatsapp_url: string, message: string,
     *               auto_configured: bool, sent: bool, send_error: ?string}
     */
    public function issue(User $actor, User $target): array
    {
        $this->guard($actor, $target);
        $phone = $this->whatsappNumber($target);
        $password = $this->generate();
        $expiresAt = now()->addHours(self::VALID_HOURS);

        DB::transaction(function () use ($target, $password, $expiresAt) {
            $target->forceFill([
                'password' => Hash::make($password),
                'password_must_change' => true,
                'temporary_password_expires_at' => $expiresAt,
            ])->save();

            // La clave anterior deja de servir en todas partes: app (tokens) y web (sesiones).
            $target->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $target->id)->delete();
            }
        });

        // El envío va fuera de la transacción: si Meta falla, la clave ya existe y se envía a mano.
        $autoConfigured = $this->whatsapp->isConfigured();
        $send = $autoConfigured
            ? $this->whatsapp->sendTemporaryPassword($phone, [
                'name' => $this->greetingName($target),
                'password' => $password,
                'login_url' => route('login'),
                'email' => (string) $target->email,
                'expires' => $expiresAt->format('d/m/Y H:i'),
            ])
            : ['sent' => false, 'message_id' => null, 'error' => null];

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'user.temporary_password',
            'entity' => 'user',
            'entity_id' => $target->id,
            'ip_address' => Request::ip(),
            // Nunca se guarda la clave; del número solo los últimos dígitos.
            'details' => array_filter([
                'expires_at' => $expiresAt->toDateTimeString(),
                'phone' => '…' . substr($phone, -3),
                'channel' => $send['sent'] ? 'api' : 'manual',
                'message_id' => $send['message_id'],
                'send_error' => $send['error'],
            ]),
            'status' => 'success',
            'created_at' => now(),
        ]);

        $message = $this->message($target, $password, $expiresAt);

        return [
            'user' => $target,
            'password' => $password,
            'expires_at' => $expiresAt,
            'phone' => $phone,
            'whatsapp_url' => 'https://wa.me/' . $phone . '?text=' . rawurlencode($message),
            'message' => $message,
            'auto_configured' => $autoConfigured,
            'sent' => $send['sent'],
            'send_error' => $send['error'],
        ];
    }

    private function greetingName(User $target): string
    {
        return trim((string) ($target->first_name ?: $target->name));
    }

    /**
     * Número en formato internacional, solo dígitos, como lo pide wa.me.
     * Un celular boliviano de 8 dígitos (empieza con 6 o 7) recibe el código 591.
     */
    public function whatsappNumber(User $target): string
    {
        $digits = preg_replace('/\D+/', '', (string) $target->phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 8 && in_array($digits[0], ['6', '7'], true)) {
            return '591' . $digits;
        }
        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        throw new UserAdminException($digits === ''
            ? 'Este usuario no tiene un número de WhatsApp registrado. Agrégalo en «Editar» y vuelve a intentarlo.'
            : 'El número registrado (' . $target->phone . ') no es un celular válido para WhatsApp. Corrígelo en «Editar».');
    }

    private function guard(User $actor, User $target): void
    {
        if (! $actor->canIssueTemporaryPasswords()) {
            throw new UserAdminException('Solo la Dirección, el administrador principal y la Secretaría pueden generar contraseñas temporales.');
        }
        if ($actor->is($target)) {
            throw new UserAdminException('Tu propia contraseña se cambia desde «Mi perfil».');
        }
        if ($target->role === 'robot') {
            throw new UserAdminException('La cuenta del robot no usa contraseñas temporales.');
        }
        if ($actor->isDirector() && $target->role === 'admin') {
            throw new UserAdminException('El director no puede cambiar la contraseña de un administrador.');
        }
        if ($actor->isAcademic() && in_array($target->role, ['admin', 'director'], true)) {
            throw new UserAdminException('La Secretaría no puede cambiar la contraseña de administradores ni del director.');
        }
        if (! in_array(strtolower(trim((string) $target->status)), ['active', 'activo'], true)) {
            throw new UserAdminException('La cuenta está suspendida: actívala antes de enviarle una contraseña temporal.');
        }
    }

    /**
     * 10 letras y números («k7RmX9qPtz»), sin signos: la plantilla de código de WhatsApp
     * acepta hasta 15 caracteres y el botón «Copiar» la copia entera.
     */
    private function generate(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $pick = fn () => self::ALPHABET[random_int(0, $max)];

        do {
            $plain = implode('', array_map(fn () => $pick(), range(1, 10)));
        } while (! preg_match('/\d/', $plain) || ! preg_match('/[a-z]/', $plain) || ! preg_match('/[A-Z]/', $plain));

        return $plain;
    }

    private function message(User $target, string $password, Carbon $expiresAt): string
    {
        $greetingName = $this->greetingName($target);
        $org = config('yura.org_name');

        return implode("\n", array_filter([
            'Hola ' . $greetingName . '.',
            'Tu contraseña temporal de YURA' . ($org ? ' (' . $org . ')' : '') . ' es: ' . $password,
            '',
            'Entra en ' . route('login') . ' con tu correo ' . $target->email . '.',
            'Vence el ' . $expiresAt->format('d/m/Y') . ' a las ' . $expiresAt->format('H:i') . '. Al entrar tendrás que cambiarla por una tuya.',
            'No la compartas con nadie.',
        ], fn ($line) => $line !== null));
    }
}
