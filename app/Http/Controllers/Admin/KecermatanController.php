<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KecermatanExam;
use App\Services\KecermatanQuestionGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KecermatanController extends Controller
{
    protected KecermatanQuestionGenerator $generator;

    public function __construct(KecermatanQuestionGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = KecermatanExam::query()
            ->with('creator:id,name')
            ->withCount(['sessions as total_participants']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by title
        if ($request->has('search') && $request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $exams = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(fn ($exam) => [
                'id' => $exam->id,
                'title' => $exam->title,
                'duration' => $exam->duration,
                'is_active' => $exam->is_active,
                'total_participants' => $exam->total_participants,
                'creator' => $exam->creator ? $exam->creator->name : 'N/A',
                'created_at' => $exam->created_at->format('d M Y H:i'),
            ]);

        return Inertia::render('Admin/Kecermatan/Index', [
            'exams' => $exams,
            'filters' => [
                'status' => $request->status ?? '',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Kecermatan/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // Transaction: pembuatan exam + generate soal bersifat atomik.
        // Jika generate gagal, tidak ada record yatim tanpa soal.
        return \DB::transaction(function () use ($request) {
            // Create exam
            $exam = KecermatanExam::create([
                'title' => $request->title,
                'duration' => 600, // Fixed 10 minutes
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            // Generate 2000 master questions secara langsung (synchronous).
            // Generator sudah dioptimasi dengan batch insert sehingga hanya butuh
            // < 1 detik. Tidak lagi bergantung pada queue worker (QUEUE_CONNECTION=
            // database) yang sering tidak berjalan sehingga soal tidak digenerate.
            $this->generator->ensureGenerated($exam);

            return redirect()
                ->route('admin.kecermatan.index')
                ->with('success', 'Ujian kecermatan berhasil dibuat! 2000 soal otomatis telah digenerate.');
        });
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KecermatanExam $kecermatan): Response
    {
        $kecermatan->load('creator:id,name');

        return Inertia::render('Admin/Kecermatan/Edit', [
            'exam' => [
                'id' => $kecermatan->id,
                'title' => $kecermatan->title,
                'duration' => $kecermatan->duration,
                'is_active' => $kecermatan->is_active,
                'total_participants' => $kecermatan->total_participants,
                'creator' => $kecermatan->creator ? $kecermatan->creator->name : 'N/A',
                'created_at' => $kecermatan->created_at->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KecermatanExam $kecermatan)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $kecermatan->update([
            'title' => $request->title,
            'is_active' => $request->is_active,
        ]);

        return redirect()
            ->route('admin.kecermatan.index')
            ->with('success', 'Ujian kecermatan berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KecermatanExam $kecermatan)
    {
        // Check if ada peserta
        if ($kecermatan->sessions()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Tidak bisa menghapus ujian yang sudah ada peserta!');
        }

        $kecermatan->delete();

        return redirect()
            ->route('admin.kecermatan.index')
            ->with('success', 'Ujian kecermatan berhasil dihapus!');
    }

    /**
     * Show exam detail and participants
     */
    public function show(KecermatanExam $kecermatan): Response
    {
        $kecermatan->load([
            'creator:id,name',
            'sessions.student:id,name,email',
            'sessions.results'
        ]);

        // Statistics
        $stats = [
            'total_participants' => $kecermatan->total_participants,
            'total_completed' => $kecermatan->total_completed,
            'total_in_progress' => $kecermatan->total_in_progress,
            'average_score' => round($kecermatan->average_score, 2),
        ];

        // Participants list
        $participants = $kecermatan->sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'student_name' => $session->student->name,
                'student_email' => $session->student->email,
                'exam_type' => $session->exam_type,
                'status' => $session->status,
                'total_correct' => $session->total_correct,
                'total_wrong' => $session->total_wrong,
                'total_score' => $session->total_score,
                'duration' => $session->duration_in_seconds,
                'started_at' => $session->start_time ? $session->start_time->format('d M Y H:i') : '-',
                'finished_at' => $session->end_time ? $session->end_time->format('d M Y H:i') : '-',
            ];
        });

        return Inertia::render('Admin/Kecermatan/Show', [
            'exam' => [
                'id' => $kecermatan->id,
                'title' => $kecermatan->title,
                'duration' => $kecermatan->duration,
                'is_active' => $kecermatan->is_active,
                'creator' => $kecermatan->creator ? $kecermatan->creator->name : 'N/A',
                'created_at' => $kecermatan->created_at->format('d M Y H:i'),
            ],
            'stats' => $stats,
            'participants' => $participants,
        ]);
    }

    /**
     * Show student result detail
     */
    public function studentResult(KecermatanExam $kecermatan, int $sessionId): Response
    {
        $session = $kecermatan->sessions()
            ->with(['student:id,name,email', 'results', 'violations'])
            ->findOrFail($sessionId);

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

        // Detail durasi mengikuti progres sesi, termasuk sesi lama yang selesai
        // karena pelanggaran sebelum mencapai kolom 10.
        $stoppedByViolation = (int) $session->violation_count >= 3;
        $currentColumn = (int) $session->current_column;

        $columnDetails = $session->results->map(function ($result) use (
            $stoppedByViolation,
            $currentColumn
        ) {
            if ($stoppedByViolation) {
                if ($result->column_number < $currentColumn) {
                    $duration = 60;
                } elseif ($result->column_number === $currentColumn) {
                    // Gunakan nilai yang disimpan saat ujian dihentikan agar
                    // laporan admin identik dengan hasil yang dilihat siswa.
                    $duration = min(60, (int) $result->time_spent);
                } else {
                    $duration = 0;
                }
            } else {
                $duration = min(60, (int) $result->time_spent);
            }

            return [
                'column' => $result->column_number,
                'correct' => $result->correct_count,
                'wrong' => $result->wrong_count,
                'unanswered' => $result->unanswered_count,
                'time_spent' => $duration,
            ];
        })->sortBy('column')->values();

        // Violations
        $violations = $session->violations->map(function ($violation) {
            return [
                'type' => $violation->violation_label,
                'time' => $violation->violation_time->format('d M Y H:i:s'),
                'column' => $violation->column_number,
                'question' => $violation->question_number,
            ];
        });

        return Inertia::render('Admin/Kecermatan/StudentResult', [
            'exam' => [
                'id' => $kecermatan->id,
                'title' => $kecermatan->title,
            ],
            'session' => [
                'id' => $session->id,
                'student_name' => $session->student->name,
                'student_email' => $session->student->email,
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
            'violations' => $violations,
        ]);
    }
}
