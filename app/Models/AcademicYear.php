<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['year', 'name', 'is_current'];

    protected $casts = ['year' => 'integer', 'is_current' => 'boolean'];

    public static function current(): ?self
    {
        return static::query()->where('is_current', true)->first();
    }

    public static function currentId(): ?int
    {
        return static::query()->where('is_current', true)->value('id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
