<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AdminLoginController extends Controller
{
    /**
     * Handle admin login with rate limiting per email.
     */
    public function __invoke(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Rate limiting per email (bukan per IP)
        $rateLimitKey = 'admin-login:' . strtolower($request->email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$minutes} menit.",
            ])->onlyInput('email');
        }

        if (auth()->attempt($credentials, $request->remember)) {
            // Login berhasil - clear rate limit
            RateLimiter::clear($rateLimitKey);

            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        // Login gagal - increment rate limit
        RateLimiter::hit($rateLimitKey, 900); // 15 minutes

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }
}
