<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BruteForceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'victim@example.com',
            'password' => 'password123',
            'role' => 'supplier',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->from('/login')->post('/login', [
                'email' => 'victim@example.com',
                'password' => 'wrong-password',
            ]);

            $response->assertRedirect('/login');
            $response->assertSessionHasErrors(['email']);
            $this->assertGuest();
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'victim@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.',
        ]);
        $this->assertGuest();

        $lockoutAudit = AuditTrail::query()
            ->where('action', 'LOGIN_RATE_LIMITED')
            ->where('target_type', 'auth')
            ->latest('id')
            ->first();

        $this->assertNotNull($lockoutAudit);
        $this->assertSame('victim@example.com', $lockoutAudit->details['email'] ?? null);
        $this->assertSame('too_many_failed_attempts', $lockoutAudit->details['reason'] ?? null);
    }
}
