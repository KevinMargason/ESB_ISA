<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'target_type' => trim((string) $request->query('target_type', '')),
            'user_id' => (int) $request->query('user_id', 0),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];

        $query = AuditTrail::query()
            ->with('user')
            ->latest('created_at');

        if ($filters['action'] !== '') {
            $query->where('action', 'like', '%'.$filters['action'].'%');
        }

        if ($filters['target_type'] !== '') {
            $query->where('target_type', $filters['target_type']);
        }

        if ($filters['user_id'] > 0) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return view('audit.index', [
            'audits' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'actions' => AuditTrail::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'targetTypes' => AuditTrail::query()
                ->select('target_type')
                ->distinct()
                ->orderBy('target_type')
                ->pluck('target_type'),
            'users' => User::query()
                ->select(['id', 'name', 'role'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
