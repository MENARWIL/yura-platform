<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'category',
        'summary',
        'year',
        'file_path',
        'published_at',
        'active',
    ];

    protected $casts = [
        'published_at' => 'date',
        'year' => 'integer',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeCategory($query, ?string $cat)
    {
        if ($cat) {
            return $query->where('category', $cat);
        }
        return $query;
    }

    public function scopeYear($query, ?int $year)
    {
        if ($year) {
            return $query->where('year', $year);
        }
        return $query;
    }
}
