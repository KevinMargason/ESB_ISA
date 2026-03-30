<?php

namespace App\Services;

use App\Models\TransactionLog;

class IntegrityService
{
    /**
     * @return array<string, mixed>
     */
    public function verify(): array
    {
        $logs = TransactionLog::query()->orderBy('chain_index')->get();

        $previousHash = null;
        $mismatch = null;

        foreach ($logs as $log) {
            $recalculated = hash('sha256', implode('|', [
                $log->ref_type,
                (string) $log->ref_id,
                $log->event_name,
                $log->payload_hash,
                (string) $log->actor_id,
                $log->created_at->toIso8601String(),
                $previousHash ?? 'GENESIS',
            ]));

            if ($recalculated !== $log->current_hash || $log->prev_hash !== $previousHash) {
                $mismatch = [
                    'chain_index' => $log->chain_index,
                    'expected_prev_hash' => $previousHash,
                    'stored_prev_hash' => $log->prev_hash,
                    'stored_current_hash' => $log->current_hash,
                    'recalculated_current_hash' => $recalculated,
                ];
                break;
            }

            $previousHash = $log->current_hash;
        }

        return [
            'valid' => $mismatch === null,
            'total_logs' => $logs->count(),
            'checked_at' => now(),
            'mismatch' => $mismatch,
        ];
    }
}
