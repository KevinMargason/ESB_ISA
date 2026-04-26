<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Http\Request;

class AuditTrailService
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public function record(Request $request, string $action, string $targetType, ?int $targetId, ?array $details = null): AuditTrail
    {
        return AuditTrail::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now('Asia/Jakarta'),
        ]);
    }
}
