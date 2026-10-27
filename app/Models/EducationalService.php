<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * Servicios activos (lo usan las pantallas internas). No filtra enlaces.
     */
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

    /**
     * Servicios que el portal público puede mostrar: activos y con un enlace
     * de acción real (no vacío, no "#" ni "javascript:").
     *
     * Este scope solo descarta lo evidente en SQL. Para descartar también los
     * enlaces internos que apuntan a rutas inexistentes (módulos retirados)
     * usa publicList() o hasWorkingLink().
     */
    public function scopePublicVisible($query)
    {
        return $query->active()
            ->whereNotNull('action_url')
            ->whereRaw("TRIM(action_url) <> ''")
            ->where('action_url', 'not like', '#%')
            ->where('action_url', 'not like', 'javascript:%');
    }

    /**
     * Lista pública final: aplica el scope publicVisible() y además descarta
     * los servicios cuyo enlace interno no corresponde a ninguna ruta registrada.
     * No depende del nombre del servicio.
     */
    public static function publicList(?string $subsystem = null, ?int $limit = null, ?string $term = null): Collection
    {
        $items = static::publicVisible()
            ->when($subsystem, fn ($q) => $q->where('subsystem', $subsystem))
            ->when($term, function ($q) use ($term) {
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->get()
            ->filter(fn (self $service) => $service->hasWorkingLink())
            ->values();

        return $limit ? $items->take($limit)->values() : $items;
    }

    /**
     * Ruta o URL de acción normalizada: ruta interna ("/grades") o URL http(s).
     * Devuelve null si el enlace no sirve.
     */
    protected function normalizedTarget(): ?string
    {
        $url = trim((string) $this->action_url);

        if ($url === '' || str_starts_with($url, '#')) {
            return null;
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : null;
    }

    /**
     * El enlace de acción lleva a algún lado: una URL externa, o una ruta GET
     * que existe de verdad en esta aplicación.
     */
    public function hasWorkingLink(): bool
    {
        $target = $this->normalizedTarget();

        if ($target === null) {
            return false;
        }

        if (preg_match('#^https?://#i', $target) === 1) {
            $host = parse_url($target, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            // Una URL de otro sitio no se puede comprobar: se da por buena.
            if ($host !== null && strcasecmp($host, (string) $appHost) !== 0) {
                return true;
            }

            $target = parse_url($target, PHP_URL_PATH) ?: '/';
        }

        try {
            Route::getRoutes()->match(Request::create($target, 'GET'));

            return true;
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }
    }

    /**
     * URL lista para un href: las rutas internas se resuelven con url() para
     * que funcionen también cuando la app vive en una subcarpeta.
     */
    public function getLinkUrlAttribute(): ?string
    {
        $target = $this->normalizedTarget();

        if ($target === null) {
            return null;
        }

        return preg_match('#^https?://#i', $target) === 1 ? $target : url($target);
    }

    /**
     * El enlace es una página interna de la plataforma (suele pedir cuenta).
     */
    public function getIsInternalLinkAttribute(): bool
    {
        $target = $this->normalizedTarget();

        return $target !== null && str_starts_with($target, '/');
    }

    /**
     * Texto humano del subsistema ("alternativa_especial" => "Alternativa y Especial").
     */
    public static function subsystemLabel(?string $key): string
    {
        $key = (string) $key;
        $path = 'portal.subsystems.' . $key;
        $label = __($path);

        // Si no hay traducción, cae en un texto legible a partir de la clave.
        return $label !== $path ? $label : Str::headline($key);
    }

    public function getSubsystemLabelAttribute(): string
    {
        return static::subsystemLabel($this->subsystem);
    }
}
