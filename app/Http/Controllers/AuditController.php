<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $entity = $request->query('entity');
        $action = $request->query('action');

        $logs = AuditLog::with('user')
            ->when($entity, fn($q) => $q->where('entity', $entity))
            ->when($action, fn($q) => $q->where('action', $action))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $entities = AuditLog::select('entity')->distinct()->pluck('entity');
        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('admin.audit.index', compact('logs', 'entities', 'actions', 'entity', 'action'));
    }
}
