<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Clear any existing session data before authentication
        $request->session()->flush();
        
        $request->authenticate();

        // Regenerate session to prevent session fixation attacks
        $request->session()->regenerate();
        
        // Set session security markers
        $request->session()->put('last_activity', now()->timestamp);
        $request->session()->put('login_time', now()->timestamp);
        $request->session()->put('user_ip', $request->ip());
        $request->session()->put('user_agent', $request->userAgent());

        // Update last login time
        if (Auth::user() && method_exists(Auth::user(), 'updateLastLogin')) {
            Auth::user()->updateLastLogin();
        }

        // Log successful login
        Log::info('User logged in successfully', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Redirect directly to dashboard
        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        // Log logout activity
        if ($user) {
            Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'session_duration' => now()->timestamp - $request->session()->get('login_time', now()->timestamp),
            ]);
        }

        Auth::guard('web')->logout();

        // Completely flush the session
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear any remember me cookies
        if ($request->hasCookie(Auth::guard()->getRecallerName())) {
            $cookie = cookie()->forget(Auth::guard()->getRecallerName());
            return redirect('/')->withCookie($cookie)->with('message', 'You have been logged out successfully.');
        }

        return redirect('/')->with('message', 'You have been logged out successfully.');
    }
}
