<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\TrackingEvent;
use App\Services\AuditTrailService;
use App\Services\HashChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrackingController extends Controller
{
    public function store(
        Request $request,
        Item $item,
        HashChainService $hashChainService,
        AuditTrailService $auditTrailService
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Item::statuses())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $rank = [
            Item::STATUS_WAREHOUSE => 1,
            Item::STATUS_DISTRIBUTION => 2,
            Item::STATUS_CUSTOMER_RECEIVED => 3,
        ];

        if ($rank[$validated['status']] <= $rank[$item->current_status]) {
            return back()->withErrors([
                'status' => 'Status harus maju ke tahap berikutnya.',
            ]);
        }

        $oldStatus = $item->current_status;

        $item->update([
            'current_status' => $validated['status'],
        ]);

        TrackingEvent::query()->create([
            'item_id' => $item->id,
            'actor_id' => $request->user()->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'event_time' => now('Asia/Jakarta'),
        ]);

        $hashChainService->append('item', $item->id, $request->user()->id, 'STATUS_UPDATED', [
            'from' => $oldStatus,
            'to' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $auditTrailService->record(
            $request,
            'STATUS_UPDATED',
            'item',
            $item->id,
            ['from' => $oldStatus, 'to' => $validated['status']]
        );

        return back()->with('success', 'Status item berhasil diupdate.');
    }
}
