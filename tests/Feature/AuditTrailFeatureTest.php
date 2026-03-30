<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AuditTrailFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_audit_trail_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/audit-trails');

        $response->assertOk();
        $response->assertSee('Audit Trail');
    }

    public function test_supplier_is_forbidden_from_audit_trail_page(): void
    {
        $supplier = User::factory()->create([
            'role' => 'supplier',
        ]);

        $response = $this->actingAs($supplier)->get('/audit-trails');

        $response->assertForbidden();
    }

    public function test_viewing_item_with_sensitive_notes_creates_audit_event(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $supplier = User::factory()->create([
            'role' => 'supplier',
        ]);

        $item = Item::query()->create([
            'item_code' => 'ITM-SEC-001',
            'item_name' => 'Secure Package',
            'category' => 'Electronics',
            'quantity' => 10,
            'supplier_id' => $supplier->id,
            'current_status' => Item::STATUS_WAREHOUSE,
            'sensitive_notes' => Crypt::encryptString('serial=ABC123XYZ'),
        ]);

        $response = $this->actingAs($admin)->get(route('items.show', $item));

        $response->assertOk();

        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $admin->id,
            'action' => 'SENSITIVE_NOTES_VIEWED',
            'target_type' => 'item',
            'target_id' => $item->id,
        ]);
    }
}
