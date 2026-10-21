<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['year', 'name', 'is_current', 'closed_at', 'closed_by'];

    protected $casts = ['year' => 'integer', 'is_current' => 'boolean', 'closed_at' => 'datetime'];

    public static function current(): ?self
    {
        return static::query()->where('is_current', true)->first();
    }

    public static function currentId(): ?int
    {
        return static::query()->where('is_current', true)->value('id');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
