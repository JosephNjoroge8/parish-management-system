<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // Create an admin user to perform the user creation
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'password' => 'password',
                'password_confirmation' => 'password',
                'user_type' => 'user',
                'is_active' => true,
            ]);

        // Check if user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_admin' => false,
        ]);

        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_admin_users_can_be_created(): void
    {
        // Create an admin user to perform the user creation
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'phone' => '0987654321',
                'password' => 'password',
                'password_confirmation' => 'password',
                'user_type' => 'admin',
                'is_active' => true,
            ]);

        // Check if admin user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $response->assertRedirect(route('admin.users.index'));
    }
}
