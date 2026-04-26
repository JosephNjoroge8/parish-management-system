<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        $user = User::factory()->create();

        // Test the password confirmation logic directly by calling the controller method
        $controller = new ConfirmablePasswordController;

        // Create a mock request
        $request = new Request(['password' => 'password']);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock the session
        $session = new Store('test', new ArraySessionHandler(120));
        $request->setLaravelSession($session);

        try {
            $response = $controller->store($request);

            // If we get here without an exception, the password was confirmed successfully
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertStringEndsWith('/dashboard', $response->getTargetUrl());

            // Check that the session has the password confirmation timestamp
            $this->assertNotNull($session->get('auth.password_confirmed_at'));
        } catch (ValidationException $e) {
            $this->fail('Password confirmation failed: '.$e->getMessage());
        }
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $user = User::factory()->create();

        // Test the password confirmation logic directly with wrong password
        $controller = new ConfirmablePasswordController;

        // Create a mock request with wrong password
        $request = new Request(['password' => 'wrong-password']);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock the session
        $session = new Store('test', new ArraySessionHandler(120));
        $request->setLaravelSession($session);

        // Expect a ValidationException to be thrown
        $this->expectException(ValidationException::class);

        $controller->store($request);
    }
}
