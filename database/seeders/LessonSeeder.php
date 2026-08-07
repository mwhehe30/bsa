<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lesson;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lessons = [
            // Psikologi
            ['name' => 'Kecerdasan', 'category' => 'psikologi', 'order' => 1],
            ['name' => 'Kecermatan', 'category' => 'psikologi', 'order' => 2],
            ['name' => 'Kepribadian', 'category' => 'psikologi', 'order' => 3],

            // Akademik
            ['name' => 'Numerasi', 'category' => 'akademik', 'order' => 1],
            ['name' => 'Bahasa Inggris', 'category' => 'akademik', 'order' => 2],
            ['name' => 'Pengetahuan Umum', 'category' => 'akademik', 'order' => 3],
            ['name' => 'Wawasan Kebangsaan', 'category' => 'akademik', 'order' => 4],
        ];

        foreach ($lessons as $lesson) {
            Lesson::create($lesson);
        }
    }
}
