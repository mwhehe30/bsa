<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Student::firstOrCreate(
            ['email' => 'only.npc.random@gmail.com'],
            [
                'name' => 'Siswa Test',
                'password' => bcrypt('password'),
                'gender' => 'L',
                'must_change_password' => false,
                'is_active' => true,
            ]
        );
    }
}
