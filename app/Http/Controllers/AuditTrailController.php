<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);
        $query = $this->buildFilteredQuery($filters);

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

    public function exportPdf(Request $request): Response
    {
        $filters = $this->filtersFromRequest($request);
        $audits = $this->buildFilteredQuery($filters)
            ->get();

        $pdf = Pdf::loadView('pdf.audit-trail-report', [
            'audits' => $audits,
            'filters' => $filters,
            'generatedAt' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit-trails-'.now('Asia/Jakarta')->format('Ymd-His').'.pdf');
    }

    /**
     * @return array{action: string, target_type: string, user_id: int, date_from: string, date_to: string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'action' => trim((string) $request->query('action', '')),
            'target_type' => trim((string) $request->query('target_type', '')),
            'user_id' => (int) $request->query('user_id', 0),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];
    }

    /**
     * @param  array{action: string, target_type: string, user_id: int, date_from: string, date_to: string}  $filters
     */
    private function buildFilteredQuery(array $filters): Builder
    {
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

        return $query;
    }
}
