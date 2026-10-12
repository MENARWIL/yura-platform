<?php

namespace App\Http\Controllers;

use App\Models\EducationalService;
use App\Models\OfficialDocument;
use Illuminate\Http\Request;

class PublicPortalController extends Controller
{
    public function home()
    {
        $services = EducationalService::active()->get()->groupBy('subsystem');
        $recentDocuments = OfficialDocument::active()->orderBy('published_at', 'desc')->take(4)->get();
        return view('public.home', compact('services', 'recentDocuments'));
    }

    public function services(Request $request)
    {
        $subsystem = $request->validate(['subsystem' => 'nullable|string|max:50'])['subsystem'] ?? null;
        $services = EducationalService::active()
            ->when($subsystem, fn($q) => $q->where('subsystem', $subsystem))
            ->get()
            ->groupBy('subsystem');

        return view('public.services', compact('services', 'subsystem'));
    }

    public function documents(Request $request)
    {
        $filters = $request->validate([
            'category' => 'nullable|string|max:30',
            'year' => 'nullable|integer|min:1900|max:2100',
            'q' => 'nullable|string|max:100',
        ]);
        $category = $filters['category'] ?? null;
        $year = $filters['year'] ?? null;
        $search = $filters['q'] ?? null;

        $query = OfficialDocument::active();

        if ($category) {
            $query->where('category', $category);
        }

        if ($year) {
            $query->where('year', $year);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderBy('published_at', 'desc')->paginate(12)->withQueryString();
        $years = OfficialDocument::active()->select('year')->distinct()->orderBy('year', 'desc')->pluck('year');

        return view('public.documents', compact('documents', 'category', 'year', 'search', 'years'));
    }

    public function search(Request $request)
    {
        $term = mb_substr(trim((string) $request->query('q')), 0, 100);

        if (empty($term)) {
            return redirect()->route('public.home');
        }

        $documents = OfficialDocument::active()
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('summary', 'like', "%{$term}%");
            })
            ->take(8)
            ->get();

        $services = EducationalService::active()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            })
            ->take(8)
            ->get();

        return view('public.search', compact('term', 'documents', 'services'));
    }
}
