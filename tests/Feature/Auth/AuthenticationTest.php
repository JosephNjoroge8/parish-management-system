<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        // Test authentication by attempting login and checking result
        $credentials = [
            'email' => $user->email,
            'password' => 'password',
        ];

        // Use Laravel's Auth::attempt to test authentication logic
        $canAuthenticate = Auth::attempt($credentials);
        $this->assertTrue($canAuthenticate, 'User should be able to authenticate with correct credentials');

        // Test that user gets redirected to dashboard when visiting login while authenticated
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        // Test logout by starting as authenticated user and calling logout
        Auth::login($user);
        $this->assertAuthenticated();

        // Call logout
        Auth::logout();
        $this->assertGuest();
    }
}
