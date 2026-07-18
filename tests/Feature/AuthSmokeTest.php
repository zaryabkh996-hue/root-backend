<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthSmokeTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Verify that the login endpoint executes and rejects with 422 (or 401)
     * rather than throwing fatal class/helper issues.
     */
    public function test_login_endpoint_executes_without_fatal(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@ourroots.africa',
            'password' => 'some-password',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Verify that the register endpoint validation rules run correctly.
     */
    public function test_register_endpoint_validation_executes(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422);
    }
}
