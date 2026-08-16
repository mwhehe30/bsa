<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Models\KecermatanExam;
use App\Models\KecermatanQuestion;
use App\Models\KecermatanResult;
use App\Models\KecermatanSession;
use App\Models\KecermatanViolation;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class TestCenterController extends Controller
{
    private const TEST_EMAIL = 'test@buweuk.test';

    /**
     * Halaman Test Center: auto-login sebagai siswa test lalu tampilkan
     * semua ujian yang bisa dicoba tanpa perlu login manual.
     */
    public function index()
    {
        $student = $this->testStudent();

        // Auto-login sebagai siswa test agar semua route ujian (mulai, jawab,
        // submit, hasil) berfungsi seperti siswa sungguhan.
        auth()->guard('student')->login($student);

        $exams = Exam::with(['lesson', 'kecermatanExam'])->orderBy('id')->get()->map(function ($exam) {
            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'lesson' => $exam->lesson?->name ?? '-',
                'is_kecermatan' => $exam->isKecermatan() || $exam->kecermatanExam !== null,
                'duration' => $exam->duration,
                'is_active' => $exam->is_active,
            ];
        });

        // Ujian kecermatan standalone (dibuat lewat menu Kecermatan, tanpa
        // induk ujian reguler) — dimulai langsung lewat route start kecermatan.
        $standaloneKecermatan = KecermatanExam::whereNull('exam_id')
            ->orderBy('id')
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'title' => $k->title,
                'is_active' => $k->is_active,
            ]);

        return \Inertia\Inertia::render('TestCenter', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'exams' => $exams,
            'standalone_kecermatan' => $standaloneKecermatan,
        ]);
    }

    /**
     * Hapus semua percobaan siswa test (grade, sesi kecermatan, exam_group,
     * pelanggaran) agar bisa mengulang tes dari nol.
     */
    public function reset()
    {
        $student = Student::where('email', self::TEST_EMAIL)->first();

        if ($student) {
            // Hapus percobaan ujian reguler
            Grade::where('student_id', $student->id)->delete();

            // Hapus sesi kecermatan beserta data terkait
            $sessionIds = KecermatanSession::where('student_id', $student->id)->pluck('id');
            KecermatanResult::whereIn('session_id', $sessionIds)->delete();
            KecermatanViolation::whereIn('session_id', $sessionIds)->delete();
            KecermatanQuestion::whereIn('session_id', $sessionIds)->delete();
            KecermatanSession::where('student_id', $student->id)->delete();

            // Bersihkan exam_groups + pelanggaran siswa test
            $examGroupIds = ExamGroup::where('student_id', $student->id)->pluck('id');
            ExamViolation::whereIn('exam_group_id', $examGroupIds)->delete();
            ExamGroup::where('student_id', $student->id)->delete();
        }

        return redirect()->route('test.center');
    }

    private function testStudent(): Student
    {
        return Student::firstOrCreate(
            ['email' => self::TEST_EMAIL],
            [
                'name' => 'Siswa Test',
                'gender' => 'L',
                'password' => Hash::make('password'),
                'must_change_password' => false,
                'is_active' => true,
            ]
        );
    }
}
