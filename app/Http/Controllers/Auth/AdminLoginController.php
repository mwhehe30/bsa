<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\TimeNoticeMessage;
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
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Rate limiting per email (bukan per IP)
        $rateLimitKey = 'admin-login:' . strtolower($request->email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors([
                'email' => TimeNoticeMessage::retryAt(
                    'Terlalu banyak percobaan login gagal',
                    $seconds,
                ),
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
