<?php

namespace App\Http\Controllers\Student;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //validate the form data
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required',
            'otp'       => 'required|size:6',
        ]);

        // Rate limiting per email (bukan per IP) - allow multiple students dari IP yang sama
        $rateLimitKey = 'login-attempt:' . strtolower($request->email);
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);
            
            return redirect()->back()->withErrors([
                'email' => "Terlalu banyak percobaan login gagal untuk email ini. Silakan coba lagi dalam {$minutes} menit."
            ])->withInput($request->only('email'));
        }

        //cek email
        $student = Student::where('email', $request->email)->first();

        if (!$student) {
            // Increment rate limit on failed attempt
            RateLimiter::hit($rateLimitKey, 900); // 15 minutes
            
            return redirect()->back()->with('error', 'Email tidak ditemukan.')->withInput($request->only('email'));
        }

        //cek password dengan hash
        if (!Hash::check($request->password, $student->password)) {
            // Increment rate limit on failed attempt
            RateLimiter::hit($rateLimitKey, 900); // 15 minutes
            
            return redirect()->back()->with('error', 'Password salah.')->withInput($request->only('email'));
        }

        //cek OTP expired
        if (!$student->otp_expired || now()->gt($student->otp_expired)) {
            // Increment rate limit on failed attempt
            RateLimiter::hit($rateLimitKey, 900); // 15 minutes
            
            return redirect()->back()->with('error', 'Kode OTP sudah kadaluarsa. Silakan minta kode baru.')->withInput($request->only('email'));
        }

        //cek OTP dengan hash
        if (!$student->otp_code || !Hash::check($request->otp, $student->otp_code)) {
            // Increment rate limit on failed attempt
            RateLimiter::hit($rateLimitKey, 900); // 15 minutes
            
            return redirect()->back()->with('error', 'Kode OTP tidak valid.')->withInput($request->only('email'));
        }

        //cek apakah siswa aktif
        if (!$student->is_active) {
            // Don't increment rate limit for inactive account (bukan kesalahan user)
            return redirect()->back()->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi admin.')->withInput($request->only('email'));
        }

        // Login berhasil - clear rate limit untuk email ini
        RateLimiter::clear($rateLimitKey);

        //login the user
        auth()->guard('student')->login($student, $request->remember);

        //clear OTP
        $student->update([
            'otp_code' => null,
            'otp_expired' => null,
        ]);

        //redirect to dashboard
        return redirect()->route('student.dashboard');
    }
}
