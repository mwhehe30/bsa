<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        return inertia('Student/Profile/Index');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (!Hash::check($value, auth()->guard('student')->user()->password)) {
                    $fail('Password lama tidak sesuai.');
                }
            }],
            'new_password' => ['required', 'confirmed', Password::min(6)],
        ]);

        auth()->guard('student')->user()->update([
            'password' => $request->new_password,
            'must_change_password' => false,
        ]);

        return redirect()->back()->with('success', 'Password berhasil diubah.');
    }
}
