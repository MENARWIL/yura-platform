<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('sort_order')->orderBy('name')->get();

        return view('subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('subjects.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => preg_replace('/[ \t]+/u', ' ', trim((string) $request->input('name'))),
            'code' => trim((string) $request->input('code')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}]+(?:[ \t-]+[\p{L}\p{N}]+)*$/u', 'unique:subjects,name'],
            'code' => ['nullable', 'string', 'max:30', 'unique:subjects,code'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        Subject::create([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? strtoupper(trim($data['code'])) : null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active' => true,
        ]);

        return redirect()->route('subjects.index')->with('success', 'Asignatura creada correctamente.');
    }

    public function edit(Subject $subject)
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $request->merge([
            'name' => preg_replace('/[ \t]+/u', ' ', trim((string) $request->input('name'))),
            'code' => trim((string) $request->input('code')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}]+(?:[ \t-]+[\p{L}\p{N}]+)*$/u', 'unique:subjects,name,' . $subject->id],
            'code' => ['nullable', 'string', 'max:30', 'unique:subjects,code,' . $subject->id],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'active' => ['nullable', 'boolean'],
        ]);

        $subject->update([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? strtoupper(trim($data['code'])) : null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active' => (bool) ($data['active'] ?? false),
        ]);

        return redirect()->route('subjects.index')->with('success', 'Asignatura actualizada correctamente.');
    }
}