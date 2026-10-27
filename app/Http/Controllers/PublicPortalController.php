<?php

namespace App\Http\Controllers;

use App\Models\EducationalService;
use App\Models\OfficialDocument;
use App\Services\PublicStats;
use Illuminate\Http\Request;

class PublicPortalController extends Controller
{
    /** Cantidad de servicios destacados en la portada. */
    private const HOME_SERVICES = 6;

    /** Cantidad de documentos recientes en la portada. */
    private const HOME_DOCUMENTS = 4;

    public function home()
    {
        // Colección plana (no agrupada): solo servicios con enlace real.
        $services = EducationalService::publicList(limit: self::HOME_SERVICES);
        $recentDocuments = OfficialDocument::publicVisible()
            ->orderBy('published_at', 'desc')
            ->take(self::HOME_DOCUMENTS)
            ->get();
        $stats = PublicStats::summary();

        return view('public.home', compact('services', 'recentDocuments', 'stats'));
    }

    public function services(Request $request)
    {
        $subsystem = $this->cleanKey($request->query('subsystem'));

        // Todos los servicios publicables; los filtros se calculan sobre esta lista
        // para que las pestañas solo ofrezcan subsistemas que sí tienen servicios.
        $all = EducationalService::publicList();
        $subsystems = $all->pluck('subsystem')->unique()->values();

        $services = $all
            ->when($subsystem, fn ($c) => $c->where('subsystem', $subsystem))
            ->groupBy('subsystem');

        return view('public.services', compact('services', 'subsystem', 'subsystems'));
    }

    public function documents(Request $request)
    {
        $category = $request->query('category');
        $category = is_string($category) && in_array($category, OfficialDocument::CATEGORIES, true) ? $category : null;

        $year = filter_var($request->query('year'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1900, 'max_range' => 2100]]);
        $year = $year === false ? null : $year;

        $search = $this->cleanTerm($request->query('q'));

        $query = OfficialDocument::publicVisible();

        if ($category) {
            $query->where('category', $category);
        }

        if ($year) {
            $query->where('year', $year);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderBy('published_at', 'desc')->paginate(12)->withQueryString();
        $years = OfficialDocument::publicVisible()->select('year')->distinct()->orderBy('year', 'desc')->pluck('year');
        $hasFilters = $category || $year || $search !== '';
        $search = $search !== '' ? $search : null;

        return view('public.documents', compact('documents', 'category', 'year', 'search', 'years', 'hasFilters'));
    }

    public function search(Request $request)
    {
        $term = $this->cleanTerm($request->query('q'));

        if ($term === '') {
            return redirect()->route('public.home');
        }

        $documents = OfficialDocument::publicVisible()
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('summary', 'like', "%{$term}%");
            })
            ->orderBy('published_at', 'desc')
            ->take(8)
            ->get();

        $services = EducationalService::publicList(term: $term, limit: 8);

        return view('public.search', compact('term', 'documents', 'services'));
    }

    /**
     * Texto de búsqueda seguro: solo cadenas, recortadas y de hasta 100 caracteres.
     */
    private function cleanTerm(mixed $value): string
    {
        return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
    }

    /**
     * Clave tipo "alternativa_especial": solo minúsculas, números y guion bajo.
     */
    private function cleanKey(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[a-z0-9_]{1,50}$/', $value) === 1 ? $value : null;
    }
}
