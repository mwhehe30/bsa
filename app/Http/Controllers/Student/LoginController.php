<?php

namespace App\Http\Controllers\Student;

use App\Helpers\TimeNoticeMessage;
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
        // Normalisasi lebih dulu agar spasi hasil copy/paste tidak membuat OTP
        // yang sebenarnya benar ditolak.
        $request->merge([
            'email' => strtolower(trim((string) $request->email)),
            'otp' => preg_replace('/\D/', '', (string) $request->otp),
        ]);

        //validate the form data
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required',
            'otp'       => ['required', 'digits:6'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'otp.required'   => 'Kode OTP wajib diisi.',
            'otp.digits'     => 'Kode OTP harus 6 digit.',
        ]);

        // Rate limiting per email (bukan per IP) - allow multiple students dari IP yang sama
        $rateLimitKey = 'login-attempt:' . strtolower($request->email);
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return redirect()->back()->withErrors([
                'email' => TimeNoticeMessage::retryAt(
                    'Terlalu banyak percobaan login gagal untuk email ini',
                    $seconds,
                ),
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
        auth()->guard('student')->login($student, $request->boolean('remember'));

        // Login manual tidak meregenerasi session ID secara otomatis. Tanpa
        // ini, sebagian browser/proxy dapat membawa session lama ke request
        // dashboard sehingga middleware menganggap siswa belum login.
        $request->session()->regenerate();

        //clear OTP
        $student->update([
            'otp_code' => null,
            'otp_expired' => null,
        ]);

        //redirect to dashboard
        return redirect()->intended(route('student.dashboard'));
    }
}
