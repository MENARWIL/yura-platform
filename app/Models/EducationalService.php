<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subsystem',
        'description',
        'icon',
        'action_url',
        'target_audience',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }

    public function scopeSubsystem($query, ?string $sub)
    {
        if ($sub) {
            return $query->where('subsystem', $sub);
        }
        return $query;
    }
}
