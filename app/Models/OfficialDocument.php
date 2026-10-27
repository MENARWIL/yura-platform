<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfficialDocument extends Model
{
    use HasFactory;

    /** Categorías válidas (coinciden con el enum de la tabla). */
    public const CATEGORIES = ['resolucion', 'circular', 'convocatoria', 'comunicado', 'instructivo', 'reglamento'];

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

    /**
     * Documentos que el portal público puede mostrar: activos y ya publicados
     * (con fecha de publicación que no sea futura).
     */
    public function scopePublicVisible($query)
    {
        return $query->active()->whereDate('published_at', '<=', now()->toDateString());
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

    /**
     * Texto humano de la categoría ("resolucion" => "Resolución").
     */
    public static function categoryLabel(?string $key): string
    {
        $key = (string) $key;
        $path = 'portal.categories.' . $key;
        $label = __($path);

        return $label !== $path ? $label : Str::headline($key);
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::categoryLabel($this->category);
    }

    /**
     * URL real de descarga, o null si el documento no tiene archivo disponible.
     * Acepta una URL http(s) o un archivo que exista en el disco público.
     */
    public function getDownloadUrlAttribute(): ?string
    {
        $path = trim((string) $this->file_path);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . ltrim($path, '/'));
            }
        } catch (\Throwable) {
            // Ruta inválida o disco no disponible: se trata como "sin archivo".
        }

        return null;
    }

    public function getHasFileAttribute(): bool
    {
        return $this->download_url !== null;
    }
}
