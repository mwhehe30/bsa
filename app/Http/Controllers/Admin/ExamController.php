<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Imports\QuestionsImport;
use App\Imports\QuestionsWordImport;
use App\Services\KecermatanQuestionGenerator;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ExamController extends Controller
{
    protected KecermatanQuestionGenerator $generator;

    public function __construct(KecermatanQuestionGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function index()
    {
        //get per_page from request, default to 5, max 100
        $perPage = request()->get('per_page', 5);
        $perPage = min(max((int) $perPage, 5), 100);

        $exams = Exam::with('lesson')
            ->when(request()->q, function ($exams) {
                $exams = $exams->where('title', 'like', '%' . request()->q . '%')
                    ->orWhereHas('lesson', function ($q) {
                        $q->where('name', 'like', '%' . request()->q . '%');
                    });
            })
            ->withCount('questions')
            ->latest()
            ->paginate($perPage);

        // Transform untuk add kecermatan questions count
        $exams->through(function ($exam) {
            // Check if kecermatan
            $lessonName = strtolower(trim($exam->lesson->name ?? ''));
            $isKecermatan = $lessonName === 'kecermatan' || str_starts_with($lessonName, 'kecermatan ');
            
            if ($isKecermatan) {
                // Get kecermatan exam
                $kecermatanExam = \App\Models\KecermatanExam::where('exam_id', $exam->id)->first();
                if ($kecermatanExam) {
                    $exam->questions_count = $kecermatanExam->masterQuestions()->count();
                }
            }
            
            return $exam;
        });

        $exams->appends(['q' => request()->q, 'per_page' => $perPage]);

        return inertia('Admin/Exams/Index', [
            'exams' => $exams,
        ]);
    }

    public function create()
    {
        $lessons = Lesson::active()->orderBy('category')->orderBy('order')->get();

        $groupedLessons = [
            'psikologi' => $lessons->where('category', 'psikologi')->values(),
            'akademik' => $lessons->where('category', 'akademik')->values(),
        ];

        return inertia('Admin/Exams/Create', [
            'lessons' => $groupedLessons,
            'TinyMCEApiKey' => config('tinymce.api_key'),
        ]);
    }

    public function store(Request $request)
    {
        // Regular exam validation
        $request->validate([
            'title' => 'required',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'duration' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'random_question' => 'required|in:Y,N',
            'random_answer' => 'required|in:Y,N',
            'show_answer' => 'required|in:Y,N',
            'import_file' => 'nullable|mimes:csv,xls,xlsx,doc,docx|max:20480',
        ]);

        // Check if lesson is Kecermatan
        $lesson = \App\Models\Lesson::find($request->lesson_id);
        $lessonName = strtolower(trim($lesson->name ?? ''));
        $isKecermatan = $lessonName === 'kecermatan' || str_starts_with($lessonName, 'kecermatan ');

        // Transaction: pembuatan exam + soal + link kecermatan bersifat atomik.
        // Jika gagal di tengah (mis. import error), tidak ada record yatim yang
        // tertinggal (kecermatan_exams tanpa exam, atau exam tanpa soal).
        return \DB::transaction(function () use ($request, $isKecermatan) {
            if ($isKecermatan) {
                // Create kecermatan exam
                $kecermatanExam = \App\Models\KecermatanExam::create([
                    'exam_id' => null, // Will be set later
                    'title' => $request->title,
                    'duration' => $request->duration * 60, // Convert to seconds
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);

                // Create regular exam record for compatibility
                $exam = Exam::create([
                    'title' => $request->title,
                    'lesson_id' => $request->lesson_id,
                    'duration' => $request->duration,
                    'description' => $request->description,
                    'random_question' => $request->random_question,
                    'random_answer' => $request->random_answer,
                    'show_answer' => $request->show_answer,
                ]);

                // Link kecermatan exam to regular exam
                $kecermatanExam->update(['exam_id' => $exam->id]);

                // Generate 2000 master questions secara langsung (synchronous).
                // Generator sudah dioptimasi dengan batch insert sehingga hanya
                // butuh < 1 detik. Tidak lagi bergantung pada queue worker yang
                // sering tidak berjalan (QUEUE_CONNECTION=database) sehingga
                // soal tidak pernah digenerate.
                $this->generator->ensureGenerated($kecermatanExam);

                return redirect()
                    ->route('admin.exams.show', $exam->id)
                    ->with('success', 'Ujian kecermatan berhasil dibuat! 2000 soal otomatis telah digenerate.');
            }

            $exam = Exam::create([
                'title' => $request->title,
                'lesson_id' => $request->lesson_id,
                'duration' => $request->duration,
                'description' => $request->description,
                'random_question' => $request->random_question,
                'random_answer' => $request->random_answer,
                'show_answer' => $request->show_answer,
            ]);

            $exam->load('lesson');

            if ($request->has('questions') && is_array($request->questions)) {
                foreach ($request->questions as $q) {
                    if (empty(trim($q['question'] ?? '')))
                        continue;

                    $data = [
                        'exam_id' => $exam->id,
                        'question' => $q['question'],
                        'option_1' => $q['option_1'] ?? '',
                        'option_2' => $q['option_2'] ?? '',
                        'option_3' => $q['option_3'] ?? '',
                        'option_4' => $q['option_4'] ?? '',
                        'option_5' => $q['option_5'] ?? '',
                        'answer' => $exam->isPersonality() ? 1 : (int) ($q['answer'] ?? 1),
                    ];

                    if ($exam->isPersonality()) {
                        $data['points'] = [
                            '1' => (int) ($q['point_1'] ?? 5),
                            '2' => (int) ($q['point_2'] ?? 4),
                            '3' => (int) ($q['point_3'] ?? 3),
                            '4' => (int) ($q['point_4'] ?? 2),
                            '5' => (int) ($q['point_5'] ?? 1),
                        ];
                    }

                    Question::create($data);
                }
            }

            if ($request->hasFile('import_file')) {
                $file = $request->file('import_file');
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['doc', 'docx'])) {
                    $import = new QuestionsWordImport($exam->id, $exam->isPersonality());
                    $result = $import->import($file);

                    if (isset($result['undetected_answers']) && !empty($result['undetected_answers'])) {
                        session()->flash('undetected_answers', $result['undetected_answers']);
                    }

                    if (isset($result['has_warnings']) && $result['has_warnings']) {
                        session()->flash('import_warnings', $result['warnings']);
                    }

                    if (isset($result['has_errors']) && $result['has_errors']) {
                        session()->flash('import_errors', $result['errors']);
                    }
                } else {
                    $import = new QuestionsImport($exam->id, $exam->isPersonality());
                    Excel::import($import, $file);
                }
            }

            return redirect()->route('admin.exams.show', $exam->id);
        });
    }

    public function show($id)
    {
        $exam = Exam::with('lesson')->findOrFail($id);

        // Check if this is kecermatan exam
        $lessonName = strtolower(trim($exam->lesson->name ?? ''));
        $isKecermatan = $lessonName === 'kecermatan' || str_starts_with($lessonName, 'kecermatan ');

        if ($isKecermatan) {
            // Get kecermatan exam
            $kecermatanExam = \App\Models\KecermatanExam::where('exam_id', $id)->first();
            
            if ($kecermatanExam) {
                // Auto-heal: pastikan 2000 soal master tersedia sebelum ditampilkan.
                // Idempotent & race-safe (hanya generate tipe yang belum ada),
                // mencakup ujian yang dibuat sebelum fix ini / tanpa queue worker.
                $this->generator->ensureGenerated($kecermatanExam);

                //get per_page from request, default to 10
                $perPage = request()->get('per_page', 10);
                $perPage = min(max((int) $perPage, 5), 100);

                // Total soal kecermatan
                $exam->questions_total = $kecermatanExam->masterQuestions()->count();
                $exam->questions_needs_review = 0; // Kecermatan tidak ada review

                // Get master questions dengan paginasi
                $kecermatanQuestions = $kecermatanExam->masterQuestions()
                    ->orderBy('exam_type')
                    ->orderBy('column_number')
                    ->orderBy('id', 'ASC')
                    ->paginate($perPage);
                
                $kecermatanQuestions->appends(['per_page' => $perPage]);

                // Transform untuk compatibility dengan view
                $questions = $kecermatanQuestions->through(function($q) {
                    return [
                        'id' => $q->id,
                        'question' => 'Kolom ' . $q->column_number . ', Soal ' . $q->question_number,
                        'option_1' => 'A',
                        'option_2' => 'B',
                        'option_3' => 'C',
                        'option_4' => 'D',
                        'option_5' => 'E',
                        'answer' => $q->correct_answer,
                        'exam_type' => $q->exam_type,
                        'needs_review' => false,
                    ];
                });

                $exam->setRelation('questions', $questions);
                $exam->is_kecermatan = true;

                return inertia('Admin/Exams/Show', [
                    'exam' => $exam,
                    'importErrors' => [],
                    'importWarnings' => [],
                    'undetectedAnswers' => [],
                ]);
            }
        }

        // Regular exam flow
        //get per_page from request, default to 5, max 100
        $perPage = request()->get('per_page', 5);
        $perPage = min(max((int) $perPage, 5), 100);

        // Total soal keseluruhan
        $exam->questions_total = $exam->questions()->count();

        // Hitung soal yang perlu review
        $exam->questions_needs_review = $exam->questions()->where('needs_review', true)->count();

        // Soal dengan paginasi - diurutkan ASC
        $questions = $exam->questions()->orderBy('id', 'ASC')->paginate($perPage);
        $questions->appends(['per_page' => $perPage]);
        $exam->setRelation('questions', $questions);
        $exam->is_kecermatan = false;

        $undetectedAnswers = session()->get('undetected_answers', []);
        $importErrors = session()->get('import_errors', []);
        $importWarnings = session()->get('import_warnings', []);

        return inertia('Admin/Exams/Show', [
            'exam' => $exam,
            'importErrors' => $importErrors,
            'importWarnings' => $importWarnings,
            'undetectedAnswers' => $undetectedAnswers,
        ]);
    }

    public function edit($id)
    {
        $exam = Exam::with('lesson')->findOrFail($id);

        $lessons = Lesson::active()->orderBy('category')->orderBy('order')->get();
        $groupedLessons = [
            'psikologi' => $lessons->where('category', 'psikologi')->values(),
            'akademik' => $lessons->where('category', 'akademik')->values(),
        ];

        return inertia('Admin/Exams/Edit', [
            'exam' => $exam,
            'lessons' => $groupedLessons,
            'TinyMCEApiKey' => config('tinymce.api_key'),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'title' => 'required',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'duration' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'random_question' => 'required|in:Y,N',
            'random_answer' => 'required|in:Y,N',
            'show_answer' => 'required|in:Y,N',
        ]);

        // Transaction: update exam + sinkronisasi kecermatan_exams atomik
        // (jika salah satu gagal, keduanya dibatalkan agar tidak tidak konsisten).
        \DB::transaction(function () use ($request, $exam) {
            $exam->update([
                'title' => $request->title,
                'lesson_id' => $request->lesson_id,
                'duration' => $request->duration,
                'description' => $request->description,
                'random_question' => $request->random_question,
                'random_answer' => $request->random_answer,
                'show_answer' => $request->show_answer,
            ]);

            // Sinkronkan perubahan ke kecermatan_exams agar judul/durasi konsisten
            // di dashboard siswa dan laporan (durasi kecermatan tersimpan dalam detik)
            if ($exam->isKecermatan()) {
                $kecermatanExam = \App\Models\KecermatanExam::where('exam_id', $exam->id)->first();
                if ($kecermatanExam) {
                    $kecermatanExam->update([
                        'title' => $request->title,
                        'duration' => $request->duration * 60,
                    ]);
                }
            }
        });

        return redirect()->route('admin.exams.index');
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();

        return redirect()->route('admin.exams.index');
    }

    /**
     * Reset semua soal untuk ujian tertentu
     */
    public function resetQuestions($id)
    {
        $exam = Exam::findOrFail($id);
        $count = $exam->questions()->count();

        if ($count > 0) {
            $exam->questions()->delete();
            return redirect()->route('admin.exams.show', $exam->id)
                ->with('success', "Berhasil menghapus {$count} soal dari ujian \"{$exam->title}\".");
        }

        return redirect()->route('admin.exams.show', $exam->id)
            ->with('info', "Ujian \"{$exam->title}\" tidak memiliki soal untuk direset.");
    }

    public function createQuestion(Exam $exam)
    {
        $exam->load('lesson');
        return inertia('Admin/Questions/Create', [
            'exam' => $exam,
        ]);
    }

    public function storeQuestion(Request $request, Exam $exam)
    {
        $rules = [
            'question' => 'required',
            'option_1' => 'required',
            'option_2' => 'required',
            'option_3' => 'required',
            'option_4' => 'required',
            'option_5' => 'required',
        ];

        if ($exam->isMultipleChoice()) {
            $rules['answer'] = 'required|in:1,2,3,4,5';
        }

        $request->validate($rules);

        $data = [
            'exam_id' => $exam->id,
            'question' => $request->question,
            'option_1' => $request->option_1,
            'option_2' => $request->option_2,
            'option_3' => $request->option_3,
            'option_4' => $request->option_4,
            'option_5' => $request->option_5,
            'answer' => $exam->isPersonality() ? 1 : $request->answer,
        ];

        if ($exam->isPersonality()) {
            $data['points'] = [
                '1' => (int) ($request->point_1 ?? 5),
                '2' => (int) ($request->point_2 ?? 4),
                '3' => (int) ($request->point_3 ?? 3),
                '4' => (int) ($request->point_4 ?? 2),
                '5' => (int) ($request->point_5 ?? 1),
            ];
        }

        Question::create($data);

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function editQuestion(Exam $exam, Question $question)
    {
        return inertia('Admin/Questions/Edit', [
            'exam' => $exam,
            'question' => $question,
        ]);
    }

    public function updateQuestion(Request $request, Exam $exam, Question $question)
    {
        $rules = [
            'question' => 'required',
            'option_1' => 'required',
            'option_2' => 'required',
            'option_3' => 'required',
            'option_4' => 'required',
            'option_5' => 'required',
        ];

        if ($exam->isMultipleChoice()) {
            $rules['answer'] = 'required|in:1,2,3,4,5';
        }

        $request->validate($rules);

        $data = [
            'question' => $request->question,
            'option_1' => $request->option_1,
            'option_2' => $request->option_2,
            'option_3' => $request->option_3,
            'option_4' => $request->option_4,
            'option_5' => $request->option_5,
        ];

        if ($exam->isMultipleChoice()) {
            $data['answer'] = $request->answer;
        }

        if ($exam->isPersonality()) {
            $data['points'] = [
                '1' => (int) ($request->point_1 ?? 5),
                '2' => (int) ($request->point_2 ?? 4),
                '3' => (int) ($request->point_3 ?? 3),
                '4' => (int) ($request->point_4 ?? 2),
                '5' => (int) ($request->point_5 ?? 1),
            ];
        }

        $question->update($data);

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function destroyQuestion(Exam $exam, Question $question)
    {
        $question->delete();
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * Mark question as reviewed (clear needs_review flag)
     */
    public function markQuestionReviewed(Exam $exam, Question $question)
    {
        $question->needs_review = false;
        $question->review_notes = null;
        $question->save();

        return redirect()->back()->with('success', 'Soal telah ditandai sebagai sudah direview.');
    }

    /**
     * Bulk mark questions as reviewed
     */
    public function bulkMarkReviewed(Request $request, Exam $exam)
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        Question::whereIn('id', $request->question_ids)
            ->where('exam_id', $exam->id)
            ->update([
                'needs_review' => false,
                'review_notes' => null,
            ]);

        $count = count($request->question_ids);
        return redirect()->back()->with('success', "{$count} soal telah ditandai sebagai sudah direview.");
    }

    public function import(Exam $exam)
    {
        $exam->load('lesson');
        return inertia('Admin/Questions/Import', [
            'exam' => $exam
        ]);
    }

    public function storeImport(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx',
        ]);

        $import = new QuestionsImport($exam->id, $exam->isPersonality());
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function importWord(Exam $exam)
    {
        $exam->load('lesson');
        return inertia('Admin/Questions/ImportWord', [
            'exam' => $exam
        ]);
    }

    public function storeImportWord(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $import = new QuestionsWordImport($exam->id, $exam->isPersonality());
        $result = $import->import($request->file('file'));

        if ($result['success']) {
            if (isset($result['undetected_answers']) && !empty($result['undetected_answers'])) {
                session()->flash('undetected_answers', $result['undetected_answers']);
            }

            if (isset($result['has_errors']) && $result['has_errors']) {
                session()->flash('import_errors', $result['errors']);
                return redirect()->route('admin.exams.show', $exam->id)
                    ->with('warning', $result['message']);
            }

            if (isset($result['has_warnings']) && $result['has_warnings']) {
                session()->flash('import_warnings', $result['warnings']);
                return redirect()->route('admin.exams.show', $exam->id)
                    ->with('warning', $result['message']);
            }

            return redirect()->route('admin.exams.show', $exam->id)
                ->with('success', $result['message']);
        }

        return redirect()->route('admin.exams.show', $exam->id)
            ->with('error', $result['message']);
    }

    public function monitor(Request $request)
    {
        $allExams = Exam::select('id', 'title', 'lesson_id')->with('lesson')->orderBy('created_at', 'desc')->get();
        $monitorData = [];
        $selectedExam = null;

        if ($request->has('exam_id') && $request->exam_id) {
            $selectedExam = Exam::find($request->exam_id);
            if ($selectedExam) {
                $grades = \App\Models\Grade::with('student')
                    ->where('exam_id', $selectedExam->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $monitorData = $grades->map(function ($grade) {
                    $status = 'belum_mulai';
                    $label = 'Belum Mulai';

                    if ($grade->start_time && !$grade->end_time) {
                        $status = 'sedang_mengerjakan';
                        $label = 'Sedang Mengerjakan';
                    } elseif ($grade->end_time) {
                        $status = 'selesai';
                        $label = 'Selesai';
                    }

                    return [
                        'id' => $grade->id,
                        'student' => $grade->student,
                        'status' => $status,
                        'label' => $label,
                        'start_time' => $grade->start_time,
                        'end_time' => $grade->end_time,
                        'score' => $grade->grade ?? ($grade->total_points ?? null),
                    ];
                });
            }
        }

        return inertia('Admin/Exams/Monitor', [
            'allExams' => $allExams,
            'selectedExamId' => $request->exam_id,
            'exam' => $selectedExam,
            'monitorData' => $monitorData
        ]);
    }
    public function uploadImage(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,jpg,png,gif,webp,bmp|max:5120',
        ]);

        $url = \App\Helpers\ImageConverter::convertUploadedFile(
            $request->file('file'),
            'question-images',
            85
        );

        if (!$url) {
            $path = $request->file('file')->store('question-images', 'public');
            $url = \Illuminate\Support\Facades\Storage::url($path);
        }

        return response()->json(['location' => $url]);
    }
}
