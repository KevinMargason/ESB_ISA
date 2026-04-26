<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridItemEncryptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('security.hybrid_crypto.private_key_path', base_path('tests/Fixtures/keys/hybrid_private.pem'));
        config()->set('security.hybrid_crypto.public_key_path', base_path('tests/Fixtures/keys/hybrid_public.pem'));
        config()->set('security.hybrid_crypto.private_key_passphrase', 'ESBHybrid2026');
    }

    public function test_sensitive_notes_are_stored_as_hybrid_ciphertext_and_shown_as_plaintext_on_detail_page(): void
    {
        $supplier = User::factory()->create([
            'role' => 'supplier',
        ]);

        $storeResponse = $this->actingAs($supplier)->post(route('items.store'), [
            'item_code' => 'ITM-HYB-001',
            'item_name' => 'Hybrid Crypto Item',
            'category' => 'Electronics',
            'quantity' => 5,
            'sensitive_notes' => 'serial=HBRID-001',
        ]);

        $item = Item::query()->where('item_code', 'ITM-HYB-001')->firstOrFail();

        $storeResponse->assertRedirect(route('items.show', $item));
        $this->assertNotSame('serial=HBRID-001', $item->sensitive_notes);
        $this->assertStringContainsString('"version":"hybrid_v1"', (string) $item->sensitive_notes);

        $detailResponse = $this->actingAs($supplier)->get(route('items.show', $item));

        $detailResponse->assertOk();
        $detailResponse->assertSee('serial=HBRID-001');
    }
}
