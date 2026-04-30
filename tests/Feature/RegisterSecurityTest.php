<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_with_invalid_role_is_rejected_and_logged(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'role' => 'admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['role']);

        $this->assertDatabaseMissing('users', [
            'email' => 'attacker@example.com',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('audit_trails', [
            'action' => 'REGISTER_ROLE_TAMPER_ATTEMPT',
            'target_type' => 'auth',
        ]);
    }

    public function test_register_with_supplier_role_is_allowed(): void
    {
        $response = $this->post('/register', [
            'name' => 'Supplier User',
            'email' => 'supplier-user@example.com',
            'role' => 'supplier',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register.verify', ['email' => 'supplier-user@example.com']));

        $this->assertDatabaseHas('users', [
            'email' => 'supplier-user@example.com',
            'role' => 'supplier',
        ]);
    }

    public function test_register_with_kurir_role_is_allowed(): void
    {
        $response = $this->post('/register', [
            'name' => 'Kurir User',
            'email' => 'kurir-user@example.com',
            'role' => 'kurir',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register.verify', ['email' => 'kurir-user@example.com']));

        $this->assertDatabaseHas('users', [
            'email' => 'kurir-user@example.com',
            'role' => 'kurir',
        ]);

    }
}
