<?php

namespace App\Http\Middleware;

use App\Events\StudentBlockStatusChanged;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuthStudent
{
    /**
     * Route yang diizinkan selama ujian reguler berjalan.
     */
    private const REGULAR_EXAM_ALLOWED_ROUTES = [
        'student.exams.show',
        'student.exams.update_duration',
        'student.exams.answerQuestion',
        'student.exams.answerQuestions',
        'student.exams.endExam',
        'student.exam.logViolation',
        'student.exam.checkStatus',
        'student.csrfToken',
    ];

    /**
     * Route yang diizinkan selama ujian kecermatan berjalan.
     */
    private const KECERMATAN_ALLOWED_ROUTES = [
        'student.kecermatan.exam',
        'student.kecermatan.submitAnswer',
        'student.kecermatan.submitAnswers',
        'student.kecermatan.columnTimeout',
        'student.kecermatan.logViolation',
        'student.kecermatan.checkStatus',
        'student.kecermatan.forceFinish',
        'student.kecermatan.csrfToken',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // check if user is logged in
        $student = auth()->guard('student')->user();

        // if not, redirect to login page
        if (! $student) {
            return redirect('/');
        }

        // Ujian reguler yang sedang berjalan (sudah mulai, belum selesai)
        $activeGrade = \App\Models\Grade::where('student_id', $student->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        // Sesi kecermatan yang sedang berjalan
        $activeKecermatanSession = \App\Models\KecermatanSession::where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->whereNotNull('start_time')
            ->latest()
            ->first();

        $routeName = $request->route() ? $request->route()->getName() : null;

        // Ujian reguler aktif: semua route selain pendukung ujian diblokir,
        // siswa dipaksa kembali ke halaman ujian.
        if ($activeGrade && ! in_array($routeName, self::REGULAR_EXAM_ALLOWED_ROUTES)) {
            // Waktu ujian sudah habis: finalisasi dan biarkan lanjut tanpa
            // mencatat pelanggaran, sehingga siswa yang kembali setelah
            // waktunya habis langsung mendapat hasil ujian.
            if ($this->isRegularExamExpired($activeGrade)) {
                DB::transaction(function () use ($activeGrade) {
                    $locked = \App\Models\Grade::whereKey($activeGrade->id)
                        ->lockForUpdate()
                        ->first();

                    if ($locked && $locked->status === 'in_progress') {
                        $this->finalizeRegularGrade($locked);
                    }
                });

                return $next($request);
            }

            return $this->blockAndRedirectRegular($request, $student, $activeGrade);
        }

        // Ujian kecermatan aktif: semua route selain pendukung ujian diblokir,
        // siswa dipaksa kembali ke halaman ujian kecermatan.
        if ($activeKecermatanSession && ! in_array($routeName, self::KECERMATAN_ALLOWED_ROUTES)) {
            // Waktu ujian sudah habis: finalisasi dan biarkan lanjut tanpa
            // mencatat pelanggaran (lihat penjelasan ujian reguler di atas).
            if ($this->isKecermatanExpired($activeKecermatanSession)) {
                DB::transaction(function () use ($activeKecermatanSession) {
                    $locked = \App\Models\KecermatanSession::query()
                        ->whereKey($activeKecermatanSession->id)
                        ->lockForUpdate()
                        ->first();

                    if ($locked && $locked->status === 'in_progress') {
                        app(\App\Services\KecermatanSessionFinalizer::class)->finalize($locked);
                    }
                });

                return $next($request);
            }

            return $this->blockAndRedirectKecermatan($request, $student, $activeKecermatanSession);
        }

        // if user is logged in, continue to next middleware
        return $next($request);
    }

    /**
     * Catat pelanggaran ujian reguler, blokir, lalu paksa kembali ke halaman ujian.
     */
    private function blockAndRedirectRegular(Request $request, $student, $activeGrade)
    {
        // Debouncing: cegah pelanggaran ganda akibat race condition
        $violationKey = "violation:{$student->id}:{$request->path()}";

        if (Cache::has($violationKey)) {
            return $this->redirectToRegularExam($student, $activeGrade);
        }

        Cache::put($violationKey, true, now()->addMilliseconds(500));

        // Gunakan transaksi + lock agar tidak ada race condition
        DB::transaction(function () use ($student, $activeGrade, $request) {
            $examGroup = \App\Models\ExamGroup::where('student_id', $student->id)
                ->where('exam_id', $activeGrade->exam_id)
                ->lockForUpdate()
                ->first();

            if (! $examGroup) {
                return;
            }

            // Catat pelanggaran
            \App\Models\ExamViolation::create([
                'exam_group_id' => $examGroup->id,
                'exam_id' => $activeGrade->exam_id,
                'violation_type' => 'tab_switch',
                'violation_time' => \Carbon\Carbon::now(),
                'notes' => 'Mencoba keluar dari halaman ujian (navigasi ke route lain: '.$request->path().')',
            ]);

            // Tambah counter pelanggaran
            $examGroup->increment('violation_count');
            $examGroup->refresh();

            // Blokir langsung pada setiap percobaan keluar
            if (! $examGroup->is_blocked) {
                $examGroup->is_blocked = true;
                $examGroup->save();

                // Broadcast event untuk real-time update
                try {
                    broadcast(new StudentBlockStatusChanged(
                        $student->id,
                        true,
                        $examGroup->id,
                        $examGroup->violation_count
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to broadcast StudentBlockStatusChanged: '.$e->getMessage());
                }
            }
        });

        // Paksa kembali ke halaman ujian
        return $this->redirectToRegularExam($student, $activeGrade);
    }

    /**
     * Catat pelanggaran kecermatan, blokir sesi, lalu paksa kembali ke halaman ujian.
     */
    private function blockAndRedirectKecermatan(Request $request, $student, $session)
    {
        // Debouncing: cegah pelanggaran ganda akibat race condition
        $violationKey = "violation:{$student->id}:{$request->path()}";

        if (Cache::has($violationKey)) {
            return $this->redirectToKecermatanExam($session);
        }

        Cache::put($violationKey, true, now()->addMilliseconds(500));

        DB::transaction(function () use ($session, $request) {
            $lockedSession = \App\Models\KecermatanSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedSession || $lockedSession->status !== 'in_progress') {
                return;
            }

            \App\Models\KecermatanViolation::create([
                'session_id' => $lockedSession->id,
                'violation_type' => 'tab_switch',
                'violation_time' => now(),
                'notes' => 'Mencoba keluar dari halaman ujian kecermatan (navigasi ke route lain: '.$request->path().')',
            ]);

            $lockedSession->increment('violation_count');
            $lockedSession->update(['is_blocked' => true]);

            // Sync ke exam_groups agar muncul di halaman Siswa Isolir admin
            $kecermatanExam = $lockedSession->exam;
            if ($kecermatanExam && $kecermatanExam->exam_id) {
                \App\Models\ExamGroup::updateOrCreate(
                    [
                        'student_id' => $lockedSession->student_id,
                        'exam_id' => $kecermatanExam->exam_id,
                    ],
                    [
                        'is_blocked' => true,
                        'violation_count' => $lockedSession->violation_count,
                    ]
                );
            }
        });

        // Paksa kembali ke halaman ujian kecermatan
        return $this->redirectToKecermatanExam($session);
    }

    /**
     * Apakah ujian reguler sudah melewati durasinya?
     * grades.duration disimpan dalam milidetik (exam.duration * 60 * 1000).
     */
    private function isRegularExamExpired($grade): bool
    {
        if (! $grade->start_time) {
            return false;
        }

        $durationMs = $grade->duration > 0
            ? $grade->duration
            : (int) (($grade->exam?->duration ?? 0) * 60 * 1000);

        return $durationMs > 0
            && now()->timestamp - $grade->start_time->timestamp >= (int) ($durationMs / 1000);
    }

    /**
     * Finalisasi ujian reguler yang waktunya sudah habis.
     * Pemanggil wajib membungkus dalam transaksi + lockForUpdate.
     */
    private function finalizeRegularGrade($grade): void
    {
        $grade->finalizeFromAnswers($grade->exam);

        $grade->end_time = now();
        $grade->duration = 0;
        $grade->status = 'completed';
        $grade->save();

        // Keluarkan dari isolir jika sempat diblokir (ujian sudah selesai).
        \App\Models\ExamGroup::where('student_id', $grade->student_id)
            ->where('exam_id', $grade->exam_id)
            ->where('is_blocked', true)
            ->update(['is_blocked' => false]);
    }

    /**
     * Apakah sesi kecermatan sudah melewati total durasinya?
     * Fallback 10 menit (10 kolom x 60 detik) bila durasi belum diisi.
     */
    private function isKecermatanExpired($session): bool
    {
        if (! $session->start_time || ! $session->exam) {
            return false;
        }

        $durationMinutes = $session->exam->duration > 0 ? (int) $session->exam->duration : 10;

        return now()->timestamp - $session->start_time->timestamp >= $durationMinutes * 60;
    }

    private function redirectToRegularExam($student, $activeGrade)
    {
        return redirect()->route('student.exams.show', [
            'exam_id' => $activeGrade->exam_id,
            'grade_id' => $activeGrade->id,
            'page' => 1,
        ]);
    }

    private function redirectToKecermatanExam($session)
    {
        return redirect()->route('student.kecermatan.exam', [
            'session' => $session->id,
            'column' => max(1, (int) ($session->current_column ?: 1)),
            'question' => max(1, (int) ($session->current_question ?: 1)),
        ]);
    }
}
