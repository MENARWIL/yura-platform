<?php

namespace App\Http\Controllers;

use App\Models\EducationalService;
use App\Models\OfficialDocument;
use Carbon\Carbon;
use Illuminate\Http\Response;

/**
 * Archivos para buscadores: mapa del sitio (sitemap.xml) y robots.txt.
 * Solo se listan páginas públicas; nunca rutas internas ni de la API.
 */
class SeoController extends Controller
{
    /**
     * Rutas internas que los buscadores no deben recorrer.
     * Debe coincidir con public/robots.txt (lo comprueba LayoutSeoTest).
     */
    public const DISALLOWED_PATHS = [
        '/admin', '/api/', '/attendance', '/courses', '/dashboard', '/family-members',
        '/grades', '/lang/', '/login', '/logout', '/parallels', '/profile',
        '/robot-activities', '/sanctum', '/search', '/students', '/subjects',
        '/teachers', '/teaching-assignments', '/tutor', '/users',
        '/storage/students/', '/storage/users/',
    ];

    /**
     * robots.txt con la línea Sitemap calculada sola. No está registrado en las rutas:
     * el archivo estático public/robots.txt gana mientras exista. Para usarlo hay que
     * registrar Route::get('/robots.txt', [SeoController::class, 'robots']) y borrar el estático.
     */
    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Allow: /'];
        foreach (self::DISALLOWED_PATHS as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $servicesChange = $this->latestChange(EducationalService::class);
        $documentsChange = $this->latestChange(OfficialDocument::class);

        // Cada entrada: ruta pública => fecha de última modificación razonable
        // (la vista de la página o, si es más reciente, los datos que muestra).
        $pages = [
            ['url' => route('public.home'), 'lastmod' => $this->newest($this->viewDate('public/home.blade.php'), $servicesChange, $documentsChange)],
            ['url' => route('public.services'), 'lastmod' => $this->newest($this->viewDate('public/services.blade.php'), $servicesChange)],
            ['url' => route('feria'), 'lastmod' => $this->viewDate('home.blade.php')],
            ['url' => route('public.documents'), 'lastmod' => $this->newest($this->viewDate('public/documents.blade.php'), $documentsChange)],
            ['url' => route('public.privacy'), 'lastmod' => $this->viewDate('public/privacy.blade.php')],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($pages as $page) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($page['url'], ENT_XML1 | ENT_QUOTES, 'UTF-8')."</loc>\n";
            if ($page['lastmod']) {
                $xml .= '    <lastmod>'.$page['lastmod']->toDateString()."</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Fecha de modificación de una vista del portal (null si no existe). */
    private function viewDate(string $relativePath): ?Carbon
    {
        $path = resource_path('views/'.$relativePath);

        return is_file($path) ? Carbon::createFromTimestamp(filemtime($path)) : null;
    }

    /** Última actualización registrada en la tabla de un modelo (null si no hay datos o la base no responde). */
    private function latestChange(string $modelClass): ?Carbon
    {
        try {
            $value = $modelClass::query()->where('active', true)->max('updated_at');

            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** La más reciente de varias fechas (ignora las nulas). */
    private function newest(?Carbon ...$dates): ?Carbon
    {
        $dates = array_filter($dates);

        return $dates ? collect($dates)->max() : null;
    }
}
