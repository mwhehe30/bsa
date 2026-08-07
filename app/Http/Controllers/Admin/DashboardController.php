<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $students = Student::count();
        $exams = Exam::count();

        return inertia('Admin/Dashboard/Index', [
            'students' => $students,
            'exams' => $exams,
        ]);
    }
}
