<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity',
        'entity_id',
        'ip_address',
        'details',
        'status',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, string $entity, ?int $entityId = null, ?array $details = null, ?Request $request = null, string $status = 'success'): self
    {
        $userId = auth()->id();
        $ip = $request ? $request->ip() : request()->ip();

        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'ip_address' => $ip,
            'details' => $details,
            'status' => $status,
            'created_at' => now(),
        ]);
    }
}
