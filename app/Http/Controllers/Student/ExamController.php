<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ExamController extends Controller
{
    /**
     * Show exam confirmation page
     */
    public function confirmation($exam_id)
    {
        $exam = Exam::with(['lesson', 'kecermatanExam'])->withCount('questions')->findOrFail($exam_id);
        $student = auth()->guard('student')->user();

        // Check if this is kecermatan exam and get correct question count.
        // Guard terhadap record kecermatan_exams yang belum ada (mis. lesson
        // di-rename menjadi "Kecermatan") agar tidak error 500.
        if ($exam->isKecermatan() && $exam->kecermatanExam) {
            $exam->questions_count = $exam->kecermatanExam->masterQuestions()->count();
        }

        // Get latest grade untuk exam ini (jika ada - untuk tracking attempt)
        $latestGrade = Grade::where('exam_id', $exam_id)
                    ->where('student_id', $student->id)
                    ->orderBy('attempt_number', 'DESC')
                    ->first();

        // Buat object exam_group untuk kompatibilitas dengan Vue component
        $examGroup = (object) [
            'id' => $exam_id,
            'exam' => $exam,
            'student' => $student,
        ];

        return inertia('Student/Exams/Confirmation', [
            'exam_group' => $examGroup,
            'grade' => $latestGrade,
        ]);
    }

    /**
     * Start exam - create new grade record and prepare questions
     */
    public function startExam($exam_id)
    {
        $exam = Exam::with(['lesson', 'kecermatanExam'])->findOrFail($exam_id);
        $student = auth()->guard('student')->user();

        // Check if this is kecermatan exam - redirect to kecermatan flow
        if ($exam->isKecermatan()) {
            return redirect()->route('student.kecermatan.selectType', ['exam' => $exam_id]);
        }

        // Transaction + lock: pembuatan grade, jawaban, dan exam group bersifat
        // atomik (tidak ada state parsial jika gagal di tengah), dan mencegah
        // double-click membuat dua grade in_progress sekaligus.
        return \DB::transaction(function () use ($exam, $exam_id, $student) {
            // Idempotensi: jika sudah ada attempt in_progress (mis. double-click),
            // lanjutkan attempt tersebut alih-alih membuat grade baru.
            $inProgressGrade = Grade::where('exam_id', $exam_id)
                ->where('student_id', $student->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if ($inProgressGrade) {
                return redirect()->route('student.exams.show', [
                    'exam_id' => $exam_id,
                    'grade_id' => $inProgressGrade->id,
                    'page' => 1,
                ]);
            }

            // Calculate next attempt number
            $attemptNumber = Grade::where('exam_id', $exam_id)
                                ->where('student_id', $student->id)
                                ->max('attempt_number') ?? 0;
            $attemptNumber++;

            // Create new grade record
            $grade = Grade::create([
                'exam_id' => $exam_id,
                'student_id' => $student->id,
                'attempt_number' => $attemptNumber,
                'duration' => $exam->duration * 60 * 1000, // milliseconds
                'start_time' => Carbon::now(),
                'end_time' => null,
                'total_correct' => 0,
                'grade' => 0,
                'total_points' => 0,
                'max_points' => 0,
                'status' => 'in_progress',
            ]);

            // Get questions (random or ordered)
            if($exam->random_question == 'Y') {
                $questions = Question::where('exam_id', $exam_id)->inRandomOrder()->get();
            } else {
                $questions = Question::where('exam_id', $exam_id)->orderBy('id', 'ASC')->get();
            }

            $question_order = 1;

            foreach ($questions as $question) {
                $options = [1,2,3,4,5];

                // Shuffle options if needed
                if($exam->isPersonality() || $exam->random_answer == 'Y') {
                    shuffle($options);
                }

                // Create answer record for this attempt
                Answer::create([
                    'exam_id' => $exam_id,
                    'grade_id' => $grade->id,
                    'question_id' => $question->id,
                    'student_id' => $student->id,
                    'question_order' => $question_order,
                    'answer_order' => implode(",", $options),
                    'answer' => 0,
                    'is_correct' => 'N',
                    'point' => 0,
                ]);

                $question_order++;
            }

            // FIX BUG: Create or reset ExamGroup for this exam attempt (clear old violations)
            $examGroup = ExamGroup::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'exam_id' => $exam_id,
                ],
                [
                    'is_blocked' => false,
                    'violation_count' => 0,
                ]
            );

            // Delete old violations from previous attempts
            $examGroup->exam_violations()->delete();

            return redirect()->route('student.exams.show', [
                'exam_id' => $exam_id,
                'grade_id' => $grade->id,
                'page' => 1,
            ]);
        });
    }

    /**
     * Show exam question page
     */
    public function show($exam_id, $grade_id, $page)
    {
        $student = auth()->guard('student')->user();

        $exam = Exam::with('lesson')->findOrFail($exam_id);
        $grade = Grade::where('id', $grade_id)
                    ->where('student_id', $student->id)
                    ->where('exam_id', $exam_id)
                    ->firstOrFail();

        // Check if already completed
        if ($grade->status === 'completed') {
            return redirect()->route('student.exams.resultExam', [
                'grade_id' => $grade->id,
            ]);
        }

        // Get exam_group for security/violation tracking
        $exam_group = ExamGroup::where('student_id', $student->id)
                    ->where('exam_id', $exam_id)
                    ->first();

        // Get all questions for this attempt
        $all_questions = Answer::with('question')
                        ->where('student_id', $student->id)
                        ->where('grade_id', $grade_id)
                        ->orderBy('question_order', 'ASC')
                        ->get();

        $question_answered = Answer::where('student_id', $student->id)
                        ->where('grade_id', $grade_id)
                        ->where('answer', '!=', 0)
                        ->count();

        $total_questions = Answer::where('student_id', $student->id)
                        ->where('grade_id', $grade_id)
                        ->count();

        $question_active = Answer::with('question.exam')
                        ->where('student_id', $student->id)
                        ->where('grade_id', $grade_id)
                        ->where('question_order', $page)
                        ->first();

        if ($question_active) {
            $answer_order = explode(",", $question_active->answer_order);
        } else {
            $answer_order = [];
        }

        // FIX BUG #2: Calculate duration on-demand (no DB write)
        $remainingSeconds = 0;
        if ($grade->start_time) {
            $startTime = Carbon::parse($grade->start_time);
            $totalDurationSeconds = $exam->duration * 60;
            $elapsedSeconds = Carbon::now()->diffInSeconds($startTime);
            $remainingSeconds = $totalDurationSeconds - $elapsedSeconds;

            // Check if time is up
            if ($remainingSeconds <= 0) {
                return $this->autoSubmitExam($exam_id, $grade_id);
            }
        }

        return inertia('Student/Exams/Show', [
            'id' => (int) $grade_id, // grade_id for navigation (cast to int)
            'exam' => $exam,
            'grade' => $grade,
            'exam_group' => $exam_group,
            'page' => (int) $page,
            'all_questions' => $all_questions,
            'question_answered' => $question_answered,
            'total_questions' => $total_questions,
            'question_active' => $question_active,
            'answer_order' => $answer_order,
            'student_id' => $student->id,
            'server_time' => Carbon::now()->timestamp,
            'remaining_seconds' => max(0, $remainingSeconds), // Send calculated time to frontend
        ]);
    }

    /**
     * Update duration (DEPRECATED - replaced with on-demand calculation)
     * Kept for backward compatibility, now returns calculated value without DB write
     */
    public function updateDuration(Request $request, $grade_id)
    {
        $student = auth()->guard('student')->user();

        $grade = Grade::where('id', $grade_id)
            ->where('student_id', $student->id)
            ->with('exam')
            ->first();

        if (!$grade || !$grade->start_time) {
            return response()->json(['error' => 'Grade not found'], 404);
        }

        // FIX BUG #2: Calculate only, no DB write
        $startTime = Carbon::parse($grade->start_time);
        $totalDurationSeconds = $grade->exam->duration * 60;
        $elapsedSeconds = Carbon::now()->diffInSeconds($startTime);
        $remainingSeconds = max(0, $totalDurationSeconds - $elapsedSeconds);

        return response()->json([
            'success' => true,
            'server_duration' => $remainingSeconds * 1000,
            'remaining_seconds' => $remainingSeconds,
            'server_time' => Carbon::now()->timestamp,
        ]);
    }

    /**
     * Answer a question
     */
    public function answerQuestion(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'grade_id' => 'required|integer|exists:grades,id',
            'question_id' => 'required|integer|exists:questions,id',
            'answer' => 'required|integer|min:1|max:5',
        ]);

        $student = auth()->guard('student')->user();

        $grade = Grade::where('id', $validated['grade_id'])
                ->where('student_id', $student->id)
                ->with('exam')
                ->first();

        if (!$grade) {
            return response()->json(['error' => 'Grade not found'], 404);
        }

        // Check if already completed
        if ($grade->status === 'completed') {
            return response()->json(['error' => 'Exam already completed'], 422);
        }

        // SERVER-SIDE DURATION CHECK (no DB write)
        if ($grade->start_time) {
            $startTime = Carbon::parse($grade->start_time);
            $totalDurationSeconds = $grade->exam->duration * 60;
            $elapsedSeconds = Carbon::now()->diffInSeconds($startTime);
            $remainingSeconds = $totalDurationSeconds - $elapsedSeconds;

            if ($remainingSeconds <= 0) {
                return response()->json([
                    'error' => 'Waktu ujian telah habis',
                    'time_up' => true
                ], 422);
            }
        }

        $question = Question::find($validated['question_id']);
        $exam = $grade->exam;

        $answerRecord = Answer::where('grade_id', $grade->id)
                    ->where('student_id', $student->id)
                    ->where('question_id', $validated['question_id'])
                    ->first();

        if (!$answerRecord) {
            return response()->json(['error' => 'Answer record not found'], 404);
        }

        if ($exam->isPersonality()) {
            // Personality: get point from selected option
            $point = $question->getPoint($validated['answer']);

            $answerRecord->answer = $validated['answer'];
            $answerRecord->point = $point;
            $answerRecord->is_correct = 'Y';
            $answerRecord->save();
        } else {
            // Multiple choice: check correct
            $result = ($question->answer == $validated['answer']) ? 'Y' : 'N';

            $answerRecord->answer = $validated['answer'];
            $answerRecord->is_correct = $result;
            $answerRecord->save();
        }

        // Return JSON agar bisa dipakai untuk incremental save dari frontend
        return response()->json([
            'success' => true,
            'question_id' => (int) $validated['question_id'],
            'answer' => (int) $validated['answer'],
            'is_correct' => $answerRecord->is_correct,
            'point' => (int) $answerRecord->point,
            'question_answered' => Answer::where('grade_id', $grade->id)
                ->where('student_id', $student->id)
                ->where('answer', '!=', 0)
                ->count(),
        ]);
    }

    /**
     * Simpan beberapa jawaban sekaligus (batch).
     *
     * Frontend mengumpulkan jawaban secara lokal lalu mengirim 1 request per
     * beberapa jawaban (bukan 1 request per jawaban). Ini mengurangi beban
     * server dan membuat penyimpanan jauh lebih cepat saat siswa menjawab
     * dengan cepat. Endpoint ini fire-and-forget dari sisi frontend; jawaban
     * juga tetap ada di localStorage dan dikirim lengkap saat endExam.
     */
    public function answerQuestions(Request $request)
    {
        $validated = $request->validate([
            'grade_id' => 'required|integer|exists:grades,id',
            'answers' => 'required|array|max:50',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.answer' => 'required|integer|min:1|max:5',
        ]);

        $student = auth()->guard('student')->user();

        $grade = Grade::where('id', $validated['grade_id'])
                ->where('student_id', $student->id)
                ->with('exam')
                ->first();

        if (!$grade) {
            return response()->json(['error' => 'Grade not found'], 404);
        }

        if ($grade->status === 'completed') {
            return response()->json(['error' => 'Exam already completed'], 422);
        }

        // SERVER-SIDE DURATION CHECK (no DB write)
        if ($grade->start_time) {
            $startTime = Carbon::parse($grade->start_time);
            $totalDurationSeconds = $grade->exam->duration * 60;
            $elapsedSeconds = Carbon::now()->diffInSeconds($startTime);
            $remainingSeconds = $totalDurationSeconds - $elapsedSeconds;

            if ($remainingSeconds <= 0) {
                return response()->json([
                    'error' => 'Waktu ujian telah habis',
                    'time_up' => true,
                ], 422);
            }
        }

        $exam = $grade->exam;

        $questionIds = collect($validated['answers'])->pluck('question_id')->all();

        // Load semua pertanyaan & record jawaban sekaligus (hindari N+1)
        $questions = Question::whereIn('id', $questionIds)->get()->keyBy('id');
        $answerRecords = Answer::where('grade_id', $grade->id)
                    ->where('student_id', $student->id)
                    ->whereIn('question_id', $questionIds)
                    ->get()
                    ->keyBy('question_id');

        $saved = 0;
        foreach ($validated['answers'] as $item) {
            $question = $questions->get($item['question_id']);
            $answerRecord = $answerRecords->get($item['question_id']);

            if (!$question || !$answerRecord) {
                continue;
            }

            if ($exam->isPersonality()) {
                // Personality: get point from selected option
                $point = $question->getPoint($item['answer']);

                $answerRecord->answer = $item['answer'];
                $answerRecord->point = $point;
                $answerRecord->is_correct = 'Y';
            } else {
                // Multiple choice: check correct
                $result = ($question->answer == $item['answer']) ? 'Y' : 'N';

                $answerRecord->answer = $item['answer'];
                $answerRecord->is_correct = $result;
                $answerRecord->point = 0;
            }

            $answerRecord->save();
            $saved++;
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'time_up' => false,
            'question_answered' => Answer::where('grade_id', $grade->id)
                ->where('student_id', $student->id)
                ->where('answer', '!=', 0)
                ->count(),
        ]);
    }

    /**
     * End exam (submit)
     */
    public function endExam(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'exam_group_id' => 'nullable|integer',
            'grade_id' => 'nullable|integer',
            'answers' => 'nullable|array',
            'answers.*' => 'integer|min:0|max:5',
            'is_auto_submit' => 'nullable|boolean',
        ]);

        $student = auth()->guard('student')->user();

        // Frontend mengirim exam_group_id yang sebenarnya adalah grade_id
        $gradeId = $validated['exam_group_id'] ?? $validated['grade_id'] ?? null;
        
        if (!$gradeId) {
            return redirect()->route('student.dashboard')->with('error', 'Grade ID tidak valid.');
        }

        $grade = Grade::where('id', $gradeId)
                    ->where('student_id', $student->id)
                    ->with('exam')
                    ->first();

        if (!$grade) {
            return redirect()->route('student.dashboard')->with('error', 'Data ujian tidak ditemukan.');
        }

        try {
            \DB::beginTransaction();

            // Lock grade row
            $grade = Grade::where('id', $gradeId)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            // Check if already submitted
            if ($grade->status === 'completed' || $grade->end_time !== null) {
                \DB::rollBack();
                return redirect()->route('student.exams.resultExam', ['grade_id' => $grade->id]);
            }

            // OPTIMIZED: Save answers from localStorage (batch processing to prevent N+1)
            if (!empty($validated['answers']) && is_array($validated['answers'])) {
                $questionIds = array_keys($validated['answers']);
                
                // Load all questions at once (1 query instead of N)
                $questions = Question::whereIn('id', $questionIds)->get()->keyBy('id');
                
                // Load all answer records at once (1 query instead of N)
                $answerRecords = Answer::where('grade_id', $grade->id)
                    ->where('student_id', $student->id)
                    ->whereIn('question_id', $questionIds)
                    ->get()
                    ->keyBy('question_id');

                $exam = $grade->exam;
                
                // Update answers in memory first
                foreach ($validated['answers'] as $question_id => $answer_val) {
                    if (!isset($questions[$question_id]) || !isset($answerRecords[$question_id]) || $answer_val == 0) {
                        continue;
                    }

                    $question = $questions[$question_id];
                    $answerRecord = $answerRecords[$question_id];

                    if ($exam->isPersonality()) {
                        $point = $question->getPoint($answer_val);
                        $answerRecord->answer = $answer_val;
                        $answerRecord->point = $point;
                        $answerRecord->is_correct = 'Y';
                    } else {
                        $result = ($question->answer == $answer_val) ? 'Y' : 'N';
                        $answerRecord->answer = $answer_val;
                        $answerRecord->is_correct = $result;
                        $answerRecord->point = 0;
                    }
                    
                    // Save individually (still better than N+1 with queries)
                    $answerRecord->save();
                }
            }

            // Check if all questions answered (skip validation for auto-submit)
            $isAutoSubmit = $validated['is_auto_submit'] ?? false;
            
            $totalQuestions = Answer::where('grade_id', $grade->id)->count();
            $answeredCount = Answer::where('grade_id', $grade->id)
                                ->where('answer', '!=', 0)
                                ->count();

            // Validasi hanya untuk manual submit
            if (!$isAutoSubmit && $answeredCount < $totalQuestions) {
                \DB::rollBack();
                return redirect()->back()->with('error', 'Anda harus menjawab semua soal terlebih dahulu.');
            }

            $exam = $grade->exam;

            // Hitung nilai dengan aturan skoring terpusat (Grade::finalizeFromAnswers)
            $grade->finalizeFromAnswers($exam);
            $grade->end_time = Carbon::now();
            $grade->status = 'completed';
            $grade->save();

            \DB::commit();

            // Buka blokir siswa setelah ujian selesai (untuk mengeluarkan dari terisolir)
            $examGroup = \App\Models\ExamGroup::where('student_id', $student->id)
                                              ->where('exam_id', $exam->id)
                                              ->first();
            
            if ($examGroup && $examGroup->is_blocked) {
                $examGroup->update(['is_blocked' => false]);
            }

            return redirect()->route('student.exams.resultExam', [
                'grade_id' => $grade->id,
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Error endExam: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'grade_id' => $gradeId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan ujian.',
            ], 500);
        }
    }

    /**
     * Show exam result
     */
    public function resultExam($grade_id)
    {
        $student = auth()->guard('student')->user();

        // Get grade directly by ID (more reliable than time-based query)
        $grade = Grade::where('id', $grade_id)
                    ->where('student_id', $student->id)
                    ->where('status', 'completed')
                    ->with('exam.lesson')
                    ->firstOrFail();

        // Get exam group for this grade
        $examGroup = ExamGroup::with(['exam.lesson', 'student', 'exam_violations'])
                        ->where('exam_id', $grade->exam_id)
                        ->where('student_id', $student->id)
                        ->orderBy('created_at', 'DESC')
                        ->first();

        // If no exam group found, create a minimal one for display purposes
        if (!$examGroup) {
            $examGroup = (object) [
                'id' => null,
                'exam' => $grade->exam,
                'student' => $student,
                'exam_violations' => collect([]),
                'violation_count' => 0,
                'is_blocked' => false,
            ];
        }

        // Attach grade to exam_group
        $examGroup->grade = $grade;

        // Hitung status untuk kepribadian
        $lessonName = strtolower(trim($grade->exam->lesson->name));
        $isPersonality = $lessonName === 'kepribadian' || str_starts_with($lessonName, 'kepribadian ');
        
        if ($isPersonality) {
            // Kepribadian: >= 50% = baik
            $percentage = $grade->max_points > 0 ? ($grade->total_points / $grade->max_points) * 100 : 0;
            $grade->status = $percentage >= 50 ? 'baik' : 'kurang baik';
        } else {
            // Regular: >= 70% = lulus
            $grade->status = $grade->grade >= 70 ? 'lulus' : 'tidak lulus';
        }

        // Get all answers for review (if show_answer enabled)
        $answers = null;
        if ($grade->exam->show_answer == 'Y') {
            $answers = Answer::with('question')
                        ->where('grade_id', $grade->id)
                        ->where('student_id', $student->id)
                        ->orderBy('question_order', 'ASC')
                        ->get();
        }

        return inertia('Student/Exams/Result', [
            'exam_group' => $examGroup,
            'grade' => $grade,
            'answers' => $answers,
        ]);
    }

    public function downloadDiscussion($grade_id)
    {
        $student = auth()->guard('student')->user();

        $grade = Grade::where('id', $grade_id)
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->with('exam.lesson')
            ->firstOrFail();

        if ($grade->exam->isKecermatan() || !$grade->exam->discussion_file_path) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($grade->exam->discussion_file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $grade->exam->discussion_file_path,
            $grade->exam->discussion_file_name
        );
    }

    /**
     * Auto-submit exam when time is up
     */
    private function autoSubmitExam($exam_id, $grade_id)
    {
        $student = auth()->guard('student')->user();

        $grade = Grade::where('id', $grade_id)
                    ->where('student_id', $student->id)
                    ->with('exam')
                    ->first();

        if (!$grade) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Data ujian tidak ditemukan.');
        }

        try {
            \DB::beginTransaction();

            $grade = Grade::where('id', $grade_id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            // Check if already submitted
            if ($grade->status === 'completed' || $grade->end_time !== null) {
                \DB::rollBack();
                return redirect()->route('student.exams.resultExam', [
                    'grade_id' => $grade_id,
                ]);
            }

            $exam = $grade->exam;

            // Hitung nilai dengan aturan skoring terpusat (Grade::finalizeFromAnswers)
            $grade->finalizeFromAnswers($exam);

            // FIX BUG #2: Set duration to 0 (time expired)
            $grade->end_time = Carbon::now();
            $grade->duration = 0;
            $grade->status = 'completed';
            $grade->save();

            \DB::commit();

            return redirect()->route('student.exams.resultExam', [
                'grade_id' => $grade_id,
            ])->with('warning', 'Waktu ujian telah habis. Ujian diselesaikan secara otomatis.');

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Error autoSubmitExam: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'grade_id' => $grade_id,
            ]);

            return redirect()->route('student.dashboard')
                ->with('error', 'Terjadi kesalahan saat menyimpan ujian.');
        }
    }
}
