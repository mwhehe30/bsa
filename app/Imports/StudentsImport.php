<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Student([
            'name'          => $row['name'],
            'email'         => $row['email'],
            'password'      => Hash::make($row['password']),
            'gender'        => $row['gender'],
            'must_change_password' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => 'unique:students,email',
        ];
    }
}
