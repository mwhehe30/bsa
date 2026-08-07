<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'otp_code',
        'otp_expired',
        'must_change_password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'otp_code',
        'remember_token',
    ];

    protected $casts = [
        'otp_expired' => 'datetime',
        'must_change_password' => 'boolean',
        'is_active' => 'boolean',
    ];
}
