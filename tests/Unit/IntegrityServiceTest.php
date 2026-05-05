<?php

namespace Tests\Unit;

use App\Models\TransactionLog;
use App\Models\User;
use App\Services\HashChainService;
use App\Services\IntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IntegrityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_returns_valid_for_untampered_chain(): void
    {
        $this->seedIntegrityChain();

        $result = app(IntegrityService::class)->verify();

        $this->assertTrue($result['valid']);
        $this->assertSame(2, $result['total_logs']);
        $this->assertNull($result['mismatch']);
    }

    public function test_verify_detects_tampered_event_data(): void
    {
        $chain = $this->seedIntegrityChain();
        $originalCurrentHash = $chain[0]->current_hash;

        $chain[0]->update([
            'event_name' => 'ITEM_APPROVED',
        ]);

        $result = app(IntegrityService::class)->verify();

        $this->assertFalse($result['valid']);
        $this->assertSame(1, $result['mismatch']['chain_index']);
        $this->assertSame($originalCurrentHash, $result['mismatch']['stored_current_hash']);
        $this->assertNotSame(
            $result['mismatch']['stored_current_hash'],
            $result['mismatch']['recalculated_current_hash'],
        );
    }

    public function test_verify_detects_broken_prev_hash_link(): void
    {
        $chain = $this->seedIntegrityChain();
        $expectedPrevHash = $chain[0]->current_hash;

        $chain[1]->update([
            'prev_hash' => str_repeat('a', 64),
        ]);

        $result = app(IntegrityService::class)->verify();

        $this->assertFalse($result['valid']);
        $this->assertSame(2, $result['mismatch']['chain_index']);
        $this->assertSame($expectedPrevHash, $result['mismatch']['expected_prev_hash']);
        $this->assertSame(str_repeat('a', 64), $result['mismatch']['stored_prev_hash']);
    }

    /**
     * @return array<int, TransactionLog>
     */
    private function seedIntegrityChain(): array
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $service = app(HashChainService::class);
        $fixedTime = Carbon::create(2026, 5, 5, 10, 0, 0, 'Asia/Jakarta');

        Carbon::setTestNow($fixedTime);

        try {
            return [
                $service->append('item', 1001, $user->id, 'ITEM_CREATED', ['status' => 'draft']),
                $service->append('item', 1001, $user->id, 'ITEM_UPDATED', ['status' => 'approved']),
            ];
        } finally {
            Carbon::setTestNow();
        }
    }
}
