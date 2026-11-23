<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

/**
 * Robot YURA registrado (contrato v1.3). No tiene correo ni contraseña: entra con una llave de API
 * (token de Sanctum) que solo puede recibir actividades y enviar notas de su materia.
 */
class RobotDevice extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens;

    public const ABILITY_RECEIVE = 'actividades:recibir';
    public const ABILITY_SEND = 'notas:enviar';
    public const ABILITIES = [self::ABILITY_RECEIVE, self::ABILITY_SEND];

    /** Prefijo reconocible de la llave (para detectarla si se filtra en un repositorio o un chat). */
    public const KEY_PREFIX = 'yura_rbt_';

    protected $fillable = [
        'name', 'subject_id', 'teacher_user_id', 'created_by', 'status', 'key_hint',
        'key_created_at', 'last_used_at', 'last_used_ip', 'revoked_at',
    ];

    protected $casts = [
        'key_created_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
