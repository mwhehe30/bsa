<?php

namespace App\Http\Controllers\Student;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\KecermatanExam;
use App\Models\ExamGroup;
use App\Models\Lesson;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $studentId = auth()->guard('student')->user()->id;

        // Get ALL exams (termasuk kecermatan)
        $exams = Exam::with(['lesson', 'kecermatanExam'])
                    ->withCount('questions')
                    ->orderBy('created_at', 'DESC')
                    ->get();

        $availableExams = [];

        foreach ($exams as $exam) {
            // Check if this is kecermatan exam
            if ($exam->isKecermatan() || (isset($exam->lesson) && stripos($exam->lesson->name, 'Kecermatan') !== false)) {
                $kecermatanExam = KecermatanExam::firstOrCreate(
                    ['exam_id' => $exam->id],
                    [
                        'title' => $exam->title,
                        'is_active' => true,
                    ]
                );

                $exam->questions_count = $kecermatanExam->masterQuestions()->count();

                $inProgressSession = $kecermatanExam->sessions()
                    ->where('student_id', $studentId)
                    ->whereIn('status', ['preparing', 'in_progress'])
                    ->latest()
                    ->first();

                // Batas finalisasi = durasi ujian (tanpa grace, selaras dengan
                // auto-submit ujian reguler). Fallback 10 menit bila durasi
                // belum diisi (10 kolom x 60 detik).
                $expiredSeconds = ($kecermatanExam->duration > 0 ? (int) $kecermatanExam->duration : 10) * 60;

                // Auto-complete kecermatan session jika sudah melewati batas waktu
                if ($inProgressSession && $inProgressSession->start_time) {
                    $elapsed = Carbon::now()->diffInSeconds(Carbon::parse($inProgressSession->start_time));
                    if ($elapsed >= $expiredSeconds) {
                        // Transaction + lock: serialkan dengan columnTimeout/forceFinish
                        // yang mungkin berjalan bersamaan agar tidak terjadi
                        // penimpaan hasil (race condition).
                        \DB::transaction(function () use ($inProgressSession, $expiredSeconds) {
                            $lockedSession = \App\Models\KecermatanSession::query()
                                ->whereKey($inProgressSession->id)
                                ->lockForUpdate()
                                ->first();

                            // Re-check di dalam lock: mungkin sudah diselesaikan
                            // oleh forceFinish / columnTimeout / tab lain.
                            if (!$lockedSession || $lockedSession->status !== 'in_progress') {
                                return;
                            }

                            $elapsed = Carbon::now()->diffInSeconds(Carbon::parse($lockedSession->start_time));
                            if ($elapsed < $expiredSeconds) {
                                return;
                            }

                            // Isi hasil per kolom sebelum menandai selesai agar hasil
                            // tidak kosong (nilai 0) saat siswa membuka halaman hasil.
                            $generator = app(\App\Services\KecermatanQuestionGenerator::class);
                            $totalCorrect = 0;
                            $totalWrong = 0;
                            $totalUnanswered = 0;

                            for ($col = 1; $col <= 10; $col++) {
                                $result = $generator->calculateColumnResult($lockedSession->id, $col);

                                \App\Models\KecermatanResult::updateOrCreate(
                                    [
                                        'session_id' => $lockedSession->id,
                                        'column_number' => $col,
                                    ],
                                    [
                                        'total_questions' => 50,
                                        'correct_count' => $result['correct_count'],
                                        'wrong_count' => $result['wrong_count'],
                                        'unanswered_count' => $result['unanswered_count'],
                                        'time_spent' => $result['time_spent'],
                                    ]
                                );

                                $totalCorrect += $result['correct_count'];
                                $totalWrong += $result['wrong_count'];
                                $totalUnanswered += $result['unanswered_count'];
                            }

                            $lockedSession->update([
                                'status' => 'completed',
                                'end_time' => now(),
                                'total_correct' => $totalCorrect,
                                'total_wrong' => $totalWrong,
                                'total_unanswered' => $totalUnanswered,
                                'total_score' => $totalCorrect,
                            ]);
                        });
                        $inProgressSession = null;
                    }
                }

                $allCompletedSessions = $kecermatanExam->sessions()
                    ->where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->latest()
                    ->get();

                $status = 'available';
                $canStart = (bool)($exam->is_active && $kecermatanExam->is_active);

                if (!$exam->is_active || !$kecermatanExam->is_active) {
                    $status = 'unavailable';
                    $canStart = false;
                } elseif ($inProgressSession) {
                    $status = 'in_progress';
                    $canStart = false;
                }

                $statusInfo = null;
                if ($inProgressSession) {
                    $totalColumns = 10;
                    $progress = (($inProgressSession->current_column - 1) / $totalColumns) * 100;
                    $statusInfo = [
                        'current_column' => $inProgressSession->current_column,
                        'current_question' => $inProgressSession->current_question ?? 1,
                        'progress' => $progress
                    ];
                }

                $availableExams[] = [
                    'exam' => $exam,
                    'is_kecermatan' => true,
                    'status' => $status,
                    'can_start' => $canStart,
                    'session_id' => $inProgressSession ? $inProgressSession->id : null,
                    'status_info' => $statusInfo,
                    'all_sessions' => $allCompletedSessions->map(fn($s) => [
                        'id' => $s->id,
                        'total_score' => $s->total_score,
                        'created_at' => $s->created_at ? $s->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                    ])->values()->toArray(),
                ];

            } else {
                // Normal Exam
                $inProgressGrade = Grade::where('exam_id', $exam->id)
                                ->where('student_id', $studentId)
                                ->where('status', 'in_progress')
                                ->latest()
                                ->first();

                // Auto-complete normal exam grade if time expired
                if ($inProgressGrade && $inProgressGrade->start_time) {
                    $elapsedSeconds = Carbon::now()->diffInSeconds(Carbon::parse($inProgressGrade->start_time));
                    $totalSeconds = ($exam->duration ?? 0) * 60;
                    if ($totalSeconds > 0 && $elapsedSeconds >= $totalSeconds) {
                        $this->autoCompleteGrade($inProgressGrade, $exam);
                        $inProgressGrade = null;
                    }
                }

                $allGrades = Grade::where('exam_id', $exam->id)
                                ->where('student_id', $studentId)
                                ->where('status', 'completed')
                                ->orderBy('attempt_number', 'DESC')
                                ->get();

                foreach ($allGrades as $grade) {
                    $examGroup = ExamGroup::where('exam_id', $grade->exam_id)
                                    ->where('student_id', $grade->student_id)
                                    ->whereBetween('created_at', [
                                        Carbon::parse($grade->created_at)->subMinutes(5),
                                        Carbon::parse($grade->created_at)->addMinutes(5)
                                    ])
                                    ->orderBy('created_at', 'DESC')
                                    ->first();
                    $grade->exam_group_id = $examGroup ? $examGroup->id : null;
                }

                $inProgressGrade = Grade::where('exam_id', $exam->id)
                                ->where('student_id', $studentId)
                                ->where('status', 'in_progress')
                                ->first();

                $attemptCount = $allGrades->count();
                $highestGrade = $allGrades->max('grade') ?? 0;
                $averageGrade = $allGrades->avg('grade') ?? 0;

                $status = 'available';
                $canStart = (bool)$exam->is_active;

                if (!$exam->is_active) {
                    $status = 'unavailable';
                    $canStart = false;
                } elseif ($inProgressGrade) {
                    $status = 'in_progress';
                    $canStart = false;
                }

                $availableExams[] = [
                    'exam' => $exam,
                    'is_kecermatan' => false,
                    'status' => $status,
                    'can_start' => $canStart,
                    'latest_grade' => $allGrades->first(),
                    'in_progress_grade' => $inProgressGrade,
                    'attempt_count' => $attemptCount,
                    'highest_grade' => round((float)$highestGrade, 2),
                    'average_grade' => round((float)$averageGrade, 2),
                    'all_grades' => $allGrades,
                ];
            }
        }

        return inertia('Student/Dashboard/Index', [
            'available_exams' => $availableExams,
            'lessons' => Lesson::active()
                ->orderBy('order')
                ->orderBy('name')
                ->get(['id', 'name', 'thumbnail']),
        ]);
    }

    /**
     * Hitung nilai dari jawaban yang tersimpan di database, lalu tandai grade
     * selesai. Menggunakan Grade::finalizeFromAnswers (aturan skoring terpusat).
     *
     * Transaction + lockForUpdate: serialkan dengan endExam/autoSubmitExam yang
     * mungkin berjalan bersamaan (siswa submit di tab lain saat dashboard
     * membuka halaman) agar tidak terjadi lost update pada status grade.
     */
    private function autoCompleteGrade(Grade $grade, Exam $exam): void
    {
        \DB::transaction(function () use ($grade, $exam) {
            $lockedGrade = Grade::query()
                ->whereKey($grade->id)
                ->lockForUpdate()
                ->first();

            // Re-check di dalam lock: mungkin sudah diselesaikan oleh
            // endExam/autoSubmitExam/request lain.
            if (!$lockedGrade || $lockedGrade->status === 'completed' || $lockedGrade->end_time !== null) {
                return;
            }

            $lockedGrade->finalizeFromAnswers($exam);
            $lockedGrade->end_time = Carbon::now();
            $lockedGrade->status = 'completed';
            $lockedGrade->save();
        });
    }
}
