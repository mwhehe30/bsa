<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamGroup;
use App\Models\KecermatanExam;
use App\Models\KecermatanSession;
use App\Models\KecermatanQuestion;
use App\Models\KecermatanResult;
use App\Models\KecermatanViolation;
use App\Services\KecermatanQuestionGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KecermatanStudentController extends Controller
{
    protected KecermatanQuestionGenerator $generator;

    public function __construct(KecermatanQuestionGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Kembalikan CSRF token terbaru untuk sesi yang sedang berjalan.
     *
     * Dipanggil frontend saat request POST ditolak dengan status 419
     * (CSRF token mismatch), misalnya karena halaman ujian sempat disajikan
     * dari cache CDN/proxy sehingga token di meta tag sudah basi. Endpoint ini
     * sengaja GET (tidak butuh CSRF) dan berlabel no-store supaya tidak pernah
     * ikut ter-cache dengan token lama.
     */
    public function csrfToken(): \Illuminate\Http\JsonResponse
    {
        // Header no-store ditambahkan otomatis oleh middleware global
        // PreventPageCache, jadi tidak perlu di-set manual di sini.
        return response()->json(['csrf_token' => csrf_token()]);
    }

    /**
     * Show select type page
     */
    public function selectType($exam_id): Response
    {
        \Log::info('SelectType called with exam_id: ' . $exam_id);
        
        $studentId = auth()->guard('student')->id();
        
        // Get or create kecermatan exam from regular exam
        $regularExam = \App\Models\Exam::findOrFail($exam_id);
        
        \Log::info('Regular exam found: ' . $regularExam->title);
        
        $kecermatanExam = KecermatanExam::firstOrCreate([
            'exam_id' => $exam_id
        ], [
            'title' => $regularExam->title,
            'duration' => $regularExam->duration,
            'is_active' => true,
            'created_by' => $regularExam->lesson_id ?? 1,
        ]);

        \Log::info('Kecermatan exam: ' . $kecermatanExam->id);

        // Check if sudah ada session in_progress
        $inProgressSession = KecermatanSession::where('kecermatan_exam_id', $kecermatanExam->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($inProgressSession) {
            return redirect()->route('student.kecermatan.exam', [
                'session' => $inProgressSession->id,
                'column' => $inProgressSession->current_column,
                'question' => $inProgressSession->current_question,
            ]);
        }

        $data = [
            'exam' => [
                'id' => $kecermatanExam->id,
                'title' => $kecermatanExam->title,
                'duration' => $kecermatanExam->duration,
            ],
        ];
        
        \Log::info('Rendering SelectType with data: ' . json_encode($data));

        return Inertia::render('Student/Kecermatan/SelectType', $data);
    }

    /**
     * Start exam with selected type
     */
    public function startExam(Request $request, $exam_id)
    {
        $request->validate([
            'exam_type' => 'required|in:huruf,angka,simbol,gambar',
        ]);

        $studentId = auth()->guard('student')->id();
        
        // Get kecermatan exam (assume $exam_id is kecermatan_exam id)
        $exam = KecermatanExam::findOrFail($exam_id);

        // FIX BUG #4: Transaction + lock to prevent duplicate sessions from double-click
        // WITHOUT deleting old sessions (preserve multiple attempts history!)
        return \DB::transaction(function () use ($exam, $studentId, $request) {
            // Check if sudah ada session in_progress (with row lock to prevent race)
            $inProgressSession = KecermatanSession::where('kecermatan_exam_id', $exam->id)
                ->where('student_id', $studentId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if ($inProgressSession) {
                // Resume existing session - reset block status for new attempt
                $inProgressSession->update([
                    'is_blocked' => false,
                    'violation_count' => 0,
                ]);
                
                return redirect()->route('student.kecermatan.exam', [
                    'session' => $inProgressSession->id,
                    'column' => $inProgressSession->current_column,
                    'question' => $inProgressSession->current_question,
                ]);
            }

            // Idempotency: Check if session just created (within last 5 seconds)
            // This handles double-click where both requests pass the lockForUpdate check
            $recentSession = KecermatanSession::where('kecermatan_exam_id', $exam->id)
                ->where('student_id', $studentId)
                ->where('status', 'preparing')
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();
                
            if ($recentSession) {
                // Use the recently created session (idempotent behavior)
                \Log::info('Reusing recent session (double-click detected)', [
                    'student_id' => $studentId,
                    'session_id' => $recentSession->id,
                ]);
                
                // Update status if still preparing and ensure unblocked
                if ($recentSession->status === 'preparing') {
                    $recentSession->update([
                        'status' => 'in_progress',
                        'start_time' => $recentSession->start_time ?? now(),
                        'is_blocked' => false,  // Reset block status for new attempt
                        'violation_count' => 0, // Reset violations for new attempt
                    ]);
                }
                
                return redirect()->route('student.kecermatan.exam', [
                    'session' => $recentSession->id,
                    'column' => 1,
                    'question' => 1,
                ]);
            }

            // Create new session (multiple attempts allowed - old completed sessions preserved!)
            $session = KecermatanSession::create([
                'kecermatan_exam_id' => $exam->id,
                'student_id' => $studentId,
                'exam_type' => $request->exam_type,
                'status' => 'preparing',
                'current_column' => 1,
                'current_question' => 1,
                'is_blocked' => false,  // Ensure new session starts unblocked
                'violation_count' => 0, // Start with 0 violations
            ]);

            // Pastikan soal master sudah tersedia untuk tipe ini.
            // Normalnya digenerate oleh queue job, tapi jika queue belum sempat
            // memproses, generate on-demand (race-safe via row lock di service).
            $this->generator->ensureGenerated($exam, $request->exam_type);

            // Check if questions already generated for this session
            $questionCount = \DB::table('kecermatan_questions')
                ->where('session_id', $session->id)
                ->count();

            if ($questionCount === 0) {
                // Generate unique shuffled questions for this student
                $this->generator->shuffleAndCopyForSession(
                    $session->id,
                    $exam->id,
                    $request->exam_type
                );
            }

            // Mulai ujian dan timer kolom pertama pada waktu yang sama.
            // column_start_time disimpan di database agar tidak reset saat halaman di-refresh.
            $startedAt = now();
            $session->update([
                'status' => 'in_progress',
                'start_time' => $startedAt,
                'column_start_time' => $startedAt,
                'current_column' => 1,
                'current_question' => 1,
            ]);

            // Redirect to first question
            return redirect()->route('student.kecermatan.exam', [
                'session' => $session->id,
                'column' => 1,
                'question' => 1,
            ]);
        });
    }

    /**
     * Show exam page (fullscreen)
     */
    public function showExam(KecermatanSession $session, int $column, int $question): Response|\Illuminate\Http\RedirectResponse
    {
        // Verify ownership
        if ($session->student_id !== auth()->guard('student')->id()) {
            abort(403, 'Unauthorized');
        }

        // Jika session sudah selesai, langsung arahkan ke hasil
        if ($session->status === 'completed') {
            return redirect()->route('student.kecermatan.result', $session->id);
        }

        // Jika kolom di DB sudah lebih jauh dari parameter URL (misal karena F5/refresh), gunakan kolom dari DB
        if ($session->current_column && $session->current_column > $column) {
            $column = $session->current_column;
        }

        // Get all questions in session ordered by column and shuffled_order
        $allQuestionsGrouped = $session->questions()
            ->orderBy('column_number')
            ->orderBy('shuffled_order')
            ->get()
            ->groupBy('column_number');

        $allColumnsData = [];
        foreach ($allQuestionsGrouped as $colNum => $qList) {
            $allColumnsData[$colNum] = $qList->map(function ($q) {
                return [
                    'id' => $q->id,
                    'column_number' => $q->column_number,
                    'question_number' => $q->question_number,
                    'shuffled_order' => $q->shuffled_order,
                    'reference_sequence' => $q->reference_sequence,
                    'question_sequence' => $q->question_sequence,
                    'student_answer' => $q->student_answer,
                ];
            })->values();
        }

        $columnQuestions = $allColumnsData[$column] ?? collect([]);
        if (empty($columnQuestions)) {
            abort(404, 'Question not found');
        }

        $currentIndex = max(0, min($question - 1, count($columnQuestions) - 1));
        $currentQuestion = $columnQuestions[$currentIndex];

        // Get progress in current column
        $answeredInColumn = collect($columnQuestions)->filter(fn($q) => $q['student_answer'] !== null)->count();

        // Database adalah sumber waktu utama. Cache tidak dipakai sebagai sumber
        // kebenaran karena dapat hilang saat cache dibersihkan, server restart,
        // atau request ditangani worker berbeda.
        $session->refresh();

        // URL tidak boleh memajukan atau memundurkan kolom dari kondisi database.
        // Ini juga mencegah refresh yang sangat cepat saat finalisasi kolom masih berjalan
        // membuat timer kolom baru dimulai ulang.
        if ((int) $session->current_column !== $column) {
            return redirect()->route('student.kecermatan.exam', [
                'session' => $session->id,
                'column' => (int) $session->current_column,
                'question' => max(1, (int) $session->current_question),
            ]);
        }

        // Inisialisasi hanya satu kali untuk sesi lama yang column_start_time-nya null.
        if (!$session->column_start_time) {
            $session = DB::transaction(function () use ($session) {
                $lockedSession = KecermatanSession::query()
                    ->whereKey($session->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedSession->column_start_time) {
                    $lockedSession->update([
                        'column_start_time' => now(),
                    ]);
                }

                return $lockedSession->fresh();
            }, 3);
        }

        $columnStartTimestamp = $session->column_start_time->timestamp;
        $elapsed = max(0, now()->timestamp - $columnStartTimestamp);
        $remainingSeconds = (int) max(0, 60 - $elapsed);

        // Simpan posisi soal tanpa mengubah waktu mulai kolom.
        if ((int) $session->current_question !== $question) {
            $session->update(['current_question' => $question]);
        }

        // Auto timeout if time is up
        if ($remainingSeconds <= 0) {
            return $this->completeColumn($session, $column);
        }

        return Inertia::render('Student/Kecermatan/Exam', [
            'session' => [
                'id' => $session->id,
                'exam_type' => $session->exam_type,
                'current_column' => $column,
                'current_question' => $question,
                'student_id' => $session->student_id,
                'is_blocked' => $session->is_blocked,
                'violation_count' => $session->violation_count,
            ],
            'all_columns' => $allColumnsData,
            'column_questions' => $columnQuestions,
            'current_index' => $currentIndex,
            'question' => [
                'id' => $currentQuestion['id'],
                'column_number' => $currentQuestion['column_number'],
                'question_number' => $currentQuestion['question_number'],
                'reference_sequence' => $currentQuestion['reference_sequence'],
                'question_sequence' => $currentQuestion['question_sequence'],
            ],
            'progress' => [
                'answered_in_column' => $answeredInColumn,
                'total_in_column' => 50,
            ],
            'timer' => [
                'remaining_seconds' => $remainingSeconds,
                'server_time' => now()->timestamp,
            ],
            'violations' => $session->violations()->orderBy('violation_time', 'desc')->get()->map(function ($v) {
                return [
                    'violation_type' => $v->violation_type,
                    'violation_time' => $v->violation_time->format('H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Simpan satu jawaban.
     *
     * Frontend sudah menangani perpindahan soal secara lokal, sehingga method
     * ini tidak boleh melakukan redirect atau menyelesaikan kolom. Redirect dari
     * fetch akan membuat seluruh halaman ujian dimuat ulang pada setiap jawaban.
     */
    public function submitAnswer(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|exists:kecermatan_questions,id',
            'answer' => 'required|in:A,B,C,D,E',
            'time_spent' => 'required|integer|min:0',
        ]);

        $question = KecermatanQuestion::with('session')
            ->findOrFail($validated['question_id']);

        $session = $question->session;

        if ($session->student_id !== auth()->guard('student')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($session->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Ujian sudah selesai.',
            ], 409);
        }

        $isCorrect = $question->correct_answer === $validated['answer'];

        $question->update([
            'student_answer' => $validated['answer'],
            'is_correct' => $isCorrect,
            'time_spent' => $validated['time_spent'],
            'answered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'question_id' => $question->id,
            'column_number' => $question->column_number,
            'is_correct' => $isCorrect,
        ]);
    }

    /**
     * Simpan beberapa jawaban sekaligus (batch).
     *
     * Frontend mengumpulkan jawaban secara lokal lalu mengirim 1 request per
     * beberapa jawaban, bukan 1 request per jawaban. Ini mengurangi beban server
     * dan mencegah request antre melewati timeout saat server lambat.
     */
    public function submitAnswers(Request $request)
    {
        $validated = $request->validate([
            'answers' => 'required|array|max:50',
            'answers.*.question_id' => 'required|integer|exists:kecermatan_questions,id',
            'answers.*.answer' => 'required|in:A,B,C,D,E',
            'answers.*.time_spent' => 'required|integer|min:0',
        ]);

        $ids = collect($validated['answers'])->pluck('question_id')->all();

        $questions = KecermatanQuestion::with('session')
            ->whereIn('id', $ids)
            ->get();

        if ($questions->isEmpty()) {
            return response()->json(['success' => true, 'saved' => 0]);
        }

        // Semua soal harus milik sesi siswa yang sama.
        $session = $questions->first()->session;

        if ($session->student_id !== auth()->guard('student')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($questions->contains(fn ($q) => $q->session_id !== $session->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Soal tidak valid.',
            ], 400);
        }

        if ($session->status === 'completed') {
            return response()->json([
                'success' => true,
                'saved' => 0,
                'message' => 'Ujian sudah selesai.',
            ]);
        }

        $byId = $questions->keyBy('id');

        DB::transaction(function () use ($validated, $byId) {
            foreach ($validated['answers'] as $item) {
                $question = $byId->get($item['question_id']);
                if (!$question) {
                    continue;
                }

                $isCorrect = $question->correct_answer === $item['answer'];

                $question->update([
                    'student_answer' => $item['answer'],
                    'is_correct' => $isCorrect,
                    'time_spent' => $item['time_spent'],
                    'answered_at' => now(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'saved' => $questions->count(),
        ]);
    }

    /**
     * Complete current column
     */
    private function completeColumn(KecermatanSession $session, int $columnNumber)
    {
        // Calculate result for this column
        $result = $this->generator->calculateColumnResult($session->id, $columnNumber);

        // Save/Update result (prevent duplicate entry error)
        KecermatanResult::updateOrCreate(
            [
                'session_id'    => $session->id,
                'column_number' => $columnNumber,
            ],
            [
                'total_questions'  => 50,
                'correct_count'    => $result['correct_count'],
                'wrong_count'      => $result['wrong_count'],
                'unanswered_count' => $result['unanswered_count'],
                // Kolom yang selesai melalui timeout selalu berdurasi 60 detik.
                'time_spent'       => 60,
            ]
        );

        if ($columnNumber < 10) {
            // Mulai timer kolom berikutnya saat perpindahan kolom disahkan backend.
            // Jangan dibuat null karena refresh sebelum showExam selesai dapat memberi 60 detik baru.
            $session->update([
                'current_column' => $columnNumber + 1,
                'current_question' => 1,
                'column_start_time' => now(),
            ]);

            return redirect()->route('student.kecermatan.exam', [
                'session' => $session->id,
                'column' => $columnNumber + 1,
                'question' => 1,
            ]);
        } else {
            // Exam completed
            return $this->finishExam($session, true);
        }
    }

    /**
     * Finish exam
     */
    private function finishExam(KecermatanSession $session, bool $currentColumnCompleted = false)
    {
        // Idempotent: request ganda tidak perlu menghitung dan mengubah sesi lagi.
        if ($session->status === 'completed') {
            return redirect()->route('student.kecermatan.result', $session->id);
        }

        // Pastikan setiap kolom 1-10 memiliki hasil yang akurat. Kolom yang
        // finalisasinya gagal di background (mis. koneksi putus) tetap dihitung
        // di sini agar total hasil akhir selalu lengkap. updateOrCreate dipakai
        // agar aman terhadap request ganda (forceFinish berjalan tanpa lock).
        $currentColumn = (int) $session->current_column;
        $activeColumnDuration = $session->column_start_time
            ? min(60, max(0, now()->timestamp - $session->column_start_time->timestamp))
            : 0;

        for ($col = 1; $col <= 10; $col++) {
            $result = $this->generator->calculateColumnResult($session->id, $col);

            if ($col < $currentColumn || ($col === $currentColumn && $currentColumnCompleted)) {
                $columnDuration = 60;
            } elseif ($col === $currentColumn) {
                // Ujian berhenti di kolom aktif (misalnya pelanggaran ke-3).
                $columnDuration = $activeColumnDuration;
            } else {
                // Kolom setelah titik berhenti tidak pernah dikerjakan.
                $columnDuration = 0;
            }

            KecermatanResult::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'column_number' => $col,
                ],
                [
                    'total_questions' => 50,
                    'correct_count' => $result['correct_count'],
                    'wrong_count' => $result['wrong_count'],
                    'unanswered_count' => $result['unanswered_count'],
                    'time_spent' => $columnDuration,
                ]
            );
        }

        // Calculate total
        $results = KecermatanResult::where('session_id', $session->id)
            ->orderBy('column_number')
            ->get();

        $session->update([
            'status' => 'completed',
            'end_time' => now(),
            'total_correct' => $results->sum('correct_count'),
            'total_wrong' => $results->sum('wrong_count'),
            'total_unanswered' => $results->sum('unanswered_count'),
            'total_score' => $results->sum('correct_count'),
        ]);

        // Buka blokir di exam_groups setelah selesai (keluarkan dari isolir)
        $kecermatanExam = $session->exam;
        if ($kecermatanExam && $kecermatanExam->exam_id) {
            $examGroup = ExamGroup::where('student_id', $session->student_id)
                ->where('exam_id', $kecermatanExam->exam_id)
                ->first();
            if ($examGroup && $examGroup->is_blocked) {
                $examGroup->update(['is_blocked' => false]);
            }
        }

        return redirect()->route('student.kecermatan.result', $session->id);
    }

    /**
     * Handle column timeout
     */
    public function columnTimeout(Request $request, KecermatanSession $session)
    {
        $validated = $request->validate([
            'column_number' => 'required|integer|min:1|max:10',
            'answers' => 'sometimes|array|max:500',
            'answers.*.question_id' => 'required|integer|exists:kecermatan_questions,id',
            'answers.*.answer' => 'required|in:A,B,C,D,E',
            'answers.*.time_spent' => 'required|integer|min:0',
        ]);

        if ($session->student_id !== auth()->guard('student')->id()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            abort(403, 'Unauthorized');
        }

        $columnNumber = (int) $validated['column_number'];

        // Lock sesi agar dua request timeout/finalisasi tidak memproses kolom
        // yang sama secara bersamaan.
        $pendingAnswers = $validated['answers'] ?? [];

        $freshSession = DB::transaction(function () use ($session, $columnNumber, $pendingAnswers) {
            $lockedSession = KecermatanSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Simpan jawaban yang masih tertahan di browser dalam transaksi yang
            // sama dengan finalisasi. Dengan begitu hasil tidak pernah dihitung
            // sebelum jawaban terakhir benar-benar masuk ke database.
            if ($lockedSession->status !== 'completed' && !empty($pendingAnswers)) {
                $this->savePendingAnswers($lockedSession->id, $pendingAnswers);
            }

            // Request duplikat setelah ujian selesai cukup dianggap berhasil.
            if ($lockedSession->status === 'completed') {
                return $lockedSession;
            }

            // Kolom tersebut sudah pernah diselesaikan oleh request sebelumnya.
            if ($columnNumber < (int) $lockedSession->current_column) {
                return $lockedSession;
            }

            // Request out-of-order (mis. finalisasi kolom sebelumnya gagal di
            // background): proses kolom yang terlewat terlebih dahulu, jangan
            // gagalkan dengan 409 agar siswa tidak terjebak di kolom terakhir.
            while (
                $columnNumber > (int) $lockedSession->current_column
                && (int) $lockedSession->current_column < 10
            ) {
                $this->completeColumn($lockedSession, (int) $lockedSession->current_column);
            }

            // Sekarang columnNumber == current_column (atau ujian sudah selesai).
            $this->completeColumn($lockedSession, $columnNumber);

            return $lockedSession->fresh();
        }, 3);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'current_column' => $freshSession->current_column,
                'status' => $freshSession->status,
                'result_url' => $freshSession->status === 'completed'
                    ? route('student.kecermatan.result', $freshSession->id)
                    : null,
            ]);
        }

        if ($freshSession->status === 'completed') {
            return redirect()->route('student.kecermatan.result', $freshSession->id);
        }

        return redirect()->route('student.kecermatan.exam', [
            'session' => $freshSession->id,
            'column' => $freshSession->current_column,
            'question' => 1,
        ]);
    }

    /**
     * Simpan sisa jawaban yang masih tertahan di browser ke database. Dipanggil
     * dari columnTimeout dan forceFinish di dalam transaksi yang sama dengan
     * perhitungan hasil, agar hasil tidak pernah dihitung sebelum jawaban
     * terakhir benar-benar tersimpan.
     *
     * @param  int  $sessionId
     * @param  array<int, array{question_id:int, answer:string, time_spent:int}>  $answers
     */
    private function savePendingAnswers(int $sessionId, array $answers): void
    {
        if (empty($answers)) {
            return;
        }

        $questions = KecermatanQuestion::query()
            ->where('session_id', $sessionId)
            ->whereIn('id', collect($answers)->pluck('question_id'))
            ->get()
            ->keyBy('id');

        if ($questions->count() !== count($answers)) {
            abort(422, 'Terdapat jawaban yang tidak sesuai dengan sesi ujian.');
        }

        foreach ($answers as $answer) {
            $question = $questions->get($answer['question_id']);
            $question->update([
                'student_answer' => $answer['answer'],
                'is_correct' => $question->correct_answer === $answer['answer'],
                'time_spent' => $answer['time_spent'],
                'answered_at' => now(),
            ]);
        }
    }

    /**
     * Force finish exam (auto-submit due to max violations)
     */
    public function forceFinish(Request $request, KecermatanSession $session)
    {
        $validated = $request->validate([
            'answers' => 'sometimes|array|max:500',
            'answers.*.question_id' => 'required|integer|exists:kecermatan_questions,id',
            'answers.*.answer' => 'required|in:A,B,C,D,E',
            'answers.*.time_spent' => 'required|integer|min:0',
        ]);

        // Verify ownership
        if ($session->student_id !== auth()->guard('student')->id()) {
            abort(403, 'Unauthorized');
        }

        $pendingAnswers = $validated['answers'] ?? [];

        // Transaction + lock: serialkan dengan columnTimeout agar dua proses
        // tidak menghitung/menulis hasil secara bersamaan.
        return DB::transaction(function () use ($session, $pendingAnswers) {
            $lockedSession = KecermatanSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Skip if already completed
            if ($lockedSession->status === 'completed') {
                return redirect()->route('student.kecermatan.result', $lockedSession->id);
            }

            // Simpan sisa jawaban yang masih tertahan di browser dalam transaksi
            // yang sama dengan finalisasi. Hasil tidak pernah dihitung sebelum
            // jawaban terakhir benar-benar masuk ke database.
            if (!empty($pendingAnswers)) {
                $this->savePendingAnswers($lockedSession->id, $pendingAnswers);
            }

            // Simpan/update hasil untuk semua kolom (kolom aktif + kolom setelahnya)
            for ($col = $lockedSession->current_column; $col <= 10; $col++) {
                $result = $this->generator->calculateColumnResult($lockedSession->id, $col);
                KecermatanResult::updateOrCreate(
                    [
                        'session_id'    => $lockedSession->id,
                        'column_number' => $col,
                    ],
                    [
                        'total_questions'  => 50,
                        'correct_count'    => $result['correct_count'],
                        'wrong_count'      => $result['wrong_count'],
                        'unanswered_count' => $result['unanswered_count'],
                        'time_spent'       => $result['time_spent'],
                    ]
                );
            }

            // Finish exam langsung
            return $this->finishExam($lockedSession);
        }, 3);
    }

    /**
     * Log violation
     */
    public function logViolation(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:kecermatan_sessions,id',
            'violation_type' => 'required|string',
            'column_number' => 'nullable|integer',
            'question_number' => 'nullable|integer',
        ]);

        $session = KecermatanSession::findOrFail($request->session_id);

        // Verify ownership
        if ($session->student_id !== auth()->guard('student')->id()) {
            abort(403, 'Unauthorized');
        }

        // Transaction + lock: pencatatan pelanggaran, update session, dan sinkron
        // exam_groups bersifat atomik, serta counter pelanggaran tidak mungkin
        // dobel/kehilangan hit saat dua request tiba bersamaan.
        try {
            \DB::beginTransaction();

            $lockedSession = KecermatanSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Check if already blocked
            if ($lockedSession->is_blocked) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa sudah diblokir.',
                    'is_blocked' => true,
                    'violation_count' => $lockedSession->violation_count,
                    'should_auto_submit' => $lockedSession->violation_count >= 3,
                    'violations' => KecermatanViolation::where('session_id', $lockedSession->id)
                        ->orderBy('violation_time', 'desc')
                        ->get(['violation_type', 'violation_time']),
                ], 200);
            }

            // Log violation
            KecermatanViolation::create([
                'session_id' => $lockedSession->id,
                'violation_type' => 'tab_switch',
                'violation_time' => now(),
                'column_number' => $request->column_number,
                'question_number' => $request->question_number,
            ]);

            // Tambah counter secara atomik (increment), lalu blokir langsung
            $lockedSession->increment('violation_count');
            $lockedSession->update([
                'is_blocked' => true,
            ]);

            $violationCount = $lockedSession->violation_count;

            // Sync ke exam_groups agar muncul di halaman Siswa Isolir di admin
            $kecermatanExam = $lockedSession->exam;
            if ($kecermatanExam && $kecermatanExam->exam_id) {
                ExamGroup::updateOrCreate(
                    [
                        'student_id' => $lockedSession->student_id,
                        'exam_id'   => $kecermatanExam->exam_id,
                    ],
                    [
                        'is_blocked'      => true,
                        'violation_count' => $violationCount,
                    ]
                );
            }

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();

            \Log::error('Error kecermatan logViolation: ' . $e->getMessage(), [
                'session_id' => $session->id,
                'student_id' => $session->student_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencatat pelanggaran.',
            ], 500);
        }

        $shouldAutoSubmit = $violationCount >= 3;

        return response()->json([
            'success' => false,
            'message' => $shouldAutoSubmit 
                ? 'Anda diblokir karena 3x pindah tab. Ujian akan otomatis disubmit.'
                : "Anda diblokir karena pindah tab. Pelanggaran {$violationCount} dari 3. Hubungi pengawas untuk membuka blokir.",
            'is_blocked' => true,
            'violation_count' => $violationCount,
            'should_auto_submit' => $shouldAutoSubmit,
            'violations' => KecermatanViolation::where('session_id', $session->id)
                ->orderBy('violation_time', 'desc')
                ->get(['violation_type', 'violation_time'])
        ], 403);
    }

    /**
     * Check session block status (called via polling from frontend)
     */
    public function checkStatus(KecermatanSession $session)
    {
        // Verify ownership
        if ($session->student_id !== auth()->guard('student')->id()) {
            abort(403, 'Unauthorized');
        }

        // Synchronize with exam_groups if unblocked by admin
        $isBlocked = $session->is_blocked;
        $kecermatanExam = $session->exam;
        if ($kecermatanExam && $kecermatanExam->exam_id) {
            $examGroup = ExamGroup::where('student_id', $session->student_id)
                ->where('exam_id', $kecermatanExam->exam_id)
                ->first();
            if ($examGroup) {
                if (!$examGroup->is_blocked && $session->is_blocked) {
                    // FIX BUG #3: Don't reset timer on unblock, only clear block flag
                    $session->update(['is_blocked' => false]);
                    $isBlocked = false;
                    
                    \Log::info("Student unblocked by admin", [
                        'session_id' => $session->id,
                        'student_id' => $session->student_id,
                        'column' => $session->current_column,
                        'timer_preserved' => true,
                    ]);
                } elseif ($examGroup->is_blocked && !$session->is_blocked) {
                    $session->update(['is_blocked' => true]);
                    $isBlocked = true;
                }
            }
        }

        // Hitung sisa waktu langsung dari database agar konsisten saat refresh.
        $session->refresh();
        $remainingSeconds = 60;

        if ($session->column_start_time) {
            $elapsed = max(
                0,
                now()->timestamp - $session->column_start_time->timestamp
            );
            $remainingSeconds = (int) max(0, 60 - $elapsed);
        }

        return response()->json([
            'is_blocked' => $isBlocked,
            'violation_count' => $session->violation_count,
            'remaining_seconds' => $remainingSeconds,
            'violations' => $session->violations()->orderBy('violation_time', 'desc')->get()->map(function ($v) {
                return [
                    'violation_type' => $v->violation_type,
                    'violation_time' => $v->violation_time->format('H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Show result page
     */
    public function result(KecermatanSession $session): Response
    {
        // Verify ownership
        if ($session->student_id !== auth()->guard('student')->id()) {
            abort(403, 'Unauthorized');
        }

        $session->load([
            'exam',
            'results' => fn ($query) => $query->orderBy('column_number'),
        ]);

        // Data untuk grafik line chart
        $chartData = [
            'labels' => range(1, 10), // Kolom 1-10
            'correct' => [],
            'wrong' => [],
        ];

        foreach ($session->results as $result) {
            $chartData['correct'][] = $result->correct_count;
            $chartData['wrong'][] = $result->wrong_count;
        }

        // Detail per kolom
        $columnDetails = $session->results->map(function ($result) {
            return [
                'column' => $result->column_number,
                'correct' => $result->correct_count,
                'wrong' => $result->wrong_count,
                'unanswered' => $result->unanswered_count,
                'time_spent' => min(60, (int) $result->time_spent),
            ];
        })->sortBy('column')->values();

        return Inertia::render('Student/Kecermatan/Result', [
            'exam' => [
                'id' => $session->exam->id,
                'title' => $session->exam->title,
            ],
            'session' => [
                'id' => $session->id,
                'exam_type' => $session->exam_type,
                'total_correct' => $session->total_correct,
                'total_wrong' => $session->total_wrong,
                'total_score' => $session->total_score,
                'started_at' => $session->start_time ? $session->start_time->format('d M Y H:i') : '-',
                'finished_at' => $session->end_time ? $session->end_time->format('d M Y H:i') : '-',
                'duration' => $session->duration_in_seconds,
            ],
            'chartData' => $chartData,
            'columnDetails' => $columnDetails,
        ]);
    }
}
