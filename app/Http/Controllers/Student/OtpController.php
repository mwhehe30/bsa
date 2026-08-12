<?php

namespace App\Http\Controllers\Student;

use App\Helpers\TimeNoticeMessage;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpController extends Controller
{
    /**
     * Kirim kode OTP ke email siswa (dengan rate limiting berlapis).
     */
    public function __invoke(Request $request)
    {
        // Layer 1: Rate limit per IP (global protection)
        $ipKey = 'otp-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return redirect()->back()->withErrors([
                'email' => TimeNoticeMessage::retryAt(
                    'Terlalu banyak permintaan dari IP Anda',
                    $seconds,
                ),
            ]);
        }
        RateLimiter::hit($ipKey, 300); // 5 minutes

        $request->validate([
            'email' => 'required|email|exists:students,email',
            'password' => 'required',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.exists'   => 'Email tidak ditemukan.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Layer 2: Rate limit per email (prevent spam to specific user)
        $emailKey = 'otp-email:' . $request->email;
        if (RateLimiter::tooManyAttempts($emailKey, 3)) {
            $seconds = RateLimiter::availableIn($emailKey);
            return redirect()->back()->withErrors([
                'email' => TimeNoticeMessage::retryAt(
                    'Terlalu banyak permintaan OTP untuk email ini',
                    $seconds,
                ),
            ]);
        }

        $student = Student::where('email', $request->email)->first();

        // Cek password sebelum kirim OTP
        if (!Hash::check($request->password, $student->password)) {
            // Anggap sebagai percobaan gagal untuk rate limiting
            RateLimiter::hit($emailKey, 300); // 5 minutes
            return redirect()->back()->withErrors([
                'password' => 'Password salah.'
            ])->withInput($request->only('email'));
        }

        RateLimiter::hit($emailKey, 300); // 5 minutes

        // Layer 3: Check if OTP is still valid (prevent unnecessary email sends)
        if ($student->otp_expired && now()->lt($student->otp_expired)) {
            $remainingSeconds = (int) max(0, now()->diffInSeconds($student->otp_expired));
            return redirect()->back()->with('info', TimeNoticeMessage::validUntil(
                'Kode OTP masih berlaku',
                $remainingSeconds,
            ) . ' Silakan cek email Anda.');
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Hash OTP for security (prevent plain text storage)
        $student->otp_code = Hash::make($otp);
        $student->otp_expired = now()->addMinutes(5);
        $student->save();

        try {
            Mail::send('emails.otp', [
                'otp' => $otp,
                'name' => $student->name
            ], function ($message) use ($student) {
                $message->to($student->email)
                    ->subject('Kode OTP Login Ujian Online');
            });

            return redirect()->back()->with('success', 'Kode OTP telah dikirim ke email ' . $student->email . '. Cek inbox (jangan lupa folder spam).');
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Failed to send OTP email: ' . $e->getMessage(), [
                'email' => $student->email,
            ]);

            return redirect()->back()->withErrors([
                'email' => 'Gagal mengirim email. Silakan coba lagi atau hubungi administrator.'
            ]);
        }
    }
}
