<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function set(Request $request, string $locale)
    {
        $allowed = ['es', 'qu'];

        if (! in_array($locale, $allowed, true)) {
            abort(400);
        }

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()->back();
    }
}
