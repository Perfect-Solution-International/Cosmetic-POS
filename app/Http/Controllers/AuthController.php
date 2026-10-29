<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the POS login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle POS login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter your POS Username or Email.',
            'password.required' => 'Please enter your secret password / PIN.',
        ]);

        $this->ensureIsNotRateLimited($request);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Determine if user entered an email or username
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Check if user exists and is active
        $user = User::where($fieldType, $loginInput)->first();

        if ($user && $user->status !== 'active') {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'login' => 'Your operator account is currently deactivated. Please contact store management.',
            ]);
        }

        $credentials = [
            $fieldType => $loginInput,
            'password' => $password,
        ];

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'login' => 'Invalid username/email or password for Psi One Global POS.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // Update last login timestamp
        $authenticatedUser = Auth::user();
        if ($authenticatedUser instanceof User) {
            $authenticatedUser->forceFill([
                'last_login_at' => now(),
            ])->save();
        }

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Welcome to Psi One Global POS, ' . Auth::user()->name . '! Terminal ready.');
    }

    /**
     * Handle POS logout request.
     */
    public function logout(Request $request)
    {
        $userName = Auth::user()?->name ?? 'User';

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Operator ' . $userName . ' has logged out successfully.');
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('login')) . '|' . $request->ip());
    }
}

