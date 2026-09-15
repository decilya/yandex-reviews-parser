<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты авторизации.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест успешного входа.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['email' => 'test@test.com', 'password' => bcrypt('password')]);
        $response = $this->postJson('/api/login', ['email' => 'test@test.com', 'password' => 'password']);
        $response->assertStatus(200)->assertJsonStructure(['token', 'user']);
    }

    /**
     * Тест неверных учётных данных.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', ['email' => 'test@test.com', 'password' => 'wrong']);
        $response->assertStatus(422);
    }
}
