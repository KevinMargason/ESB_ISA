<?php

namespace App\Services;

use App\Models\TransactionLog;

class HashChainService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(string $refType, int $refId, int $actorId, string $eventName, array $payload): TransactionLog
    {
        $last = TransactionLog::query()->orderByDesc('chain_index')->first();

        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $prevHash = $last?->current_hash;
        $chainIndex = ($last?->chain_index ?? 0) + 1;

        $raw = implode('|', [
            $refType,
            (string) $refId,
            $eventName,
            $payloadHash,
            (string) $actorId,
            now()->toIso8601String(),
            $prevHash ?? 'GENESIS',
        ]);

        $currentHash = hash('sha256', $raw);

        return TransactionLog::query()->create([
            'ref_type' => $refType,
            'ref_id' => $refId,
            'actor_id' => $actorId,
            'event_name' => $eventName,
            'payload_hash' => $payloadHash,
            'prev_hash' => $prevHash,
            'current_hash' => $currentHash,
            'chain_index' => $chainIndex,
            'created_at' => now(),
        ]);
    }
}
