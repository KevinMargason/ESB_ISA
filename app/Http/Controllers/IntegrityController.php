<?php

namespace App\Http\Controllers;

use App\Models\TransactionLog;
use App\Services\AuditTrailService;
use App\Services\IntegrityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrityController extends Controller
{
    public function index(
        Request $request,
        IntegrityService $integrityService,
        AuditTrailService $auditTrailService
    ): View
    {
        $result = $integrityService->verify();

        $auditTrailService->record(
            $request,
            'INTEGRITY_CHECK_EXECUTED',
            'integrity',
            null,
            [
                'valid' => $result['valid'],
                'total_logs' => $result['total_logs'],
                'mismatch_chain_index' => $result['mismatch']['chain_index'] ?? null,
            ]
        );

        return view('integrity.index', [
            'result' => $result,
            'latestLogs' => TransactionLog::query()
                ->with('actor')
                ->orderByDesc('chain_index')
                ->limit(20)
                ->get(),
        ]);
    }
}
