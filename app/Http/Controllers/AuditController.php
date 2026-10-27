<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AuditPresenter;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $entity = $request->query('entity');
        $action = $request->query('action');

        $logs = AuditLog::with('user')
            // El módulo se compara sin distinguir mayúsculas: 'Student' y 'student' son lo mismo.
            ->when($entity, fn ($q) => $q->whereRaw('LOWER(entity) = ?', [mb_strtolower((string) $entity)]))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Los filtros muestran nombres simples; el valor que se envía sigue siendo el del registro.
        $entities = AuditLog::select('entity')->distinct()->pluck('entity')
            ->map(fn ($e) => mb_strtolower((string) $e))->unique()
            ->mapWithKeys(fn ($e) => [$e => AuditPresenter::entity($e)])->sort();
        $actions = AuditLog::select('action')->distinct()->pluck('action')
            ->mapWithKeys(fn ($a) => [$a => AuditPresenter::action($a)])->sort();

        $names = AuditPresenter::names($logs->getCollection());

        return view('admin.audit.index', compact('logs', 'entities', 'actions', 'entity', 'action', 'names'));
    }
}
