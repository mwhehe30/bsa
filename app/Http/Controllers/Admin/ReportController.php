<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\ExamGroup;
use App\Models\KecermatanSession;
use Illuminate\Http\Request;
use App\Exports\GradesExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        //get per_page from request, default to 10, max 100
        $perPage = request()->get('per_page', 10);
        $perPage = min(max((int) $perPage, 5), 100);

        $exams = Exam::with('lesson')->orderBy('created_at', 'desc')->get();
        $lessons = Lesson::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        $query = Grade::with(['student', 'exam.lesson']);

        // Support filter params
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLower = mb_strtolower($search);

            $query->where(function ($q) use ($searchLower) {
                $q->whereHas('student', function ($sq) use ($searchLower) {
                    $sq->whereRaw('LOWER(name) LIKE ?', ['%' . $searchLower . '%']);
                })
                    ->orWhereHas('exam', function ($eq) use ($searchLower) {
                        $eq->whereRaw('LOWER(title) LIKE ?', ['%' . $searchLower . '%']);
                    });
            });
        }

        $grades = $query->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Manual load exam groups dengan violations untuk setiap grade
        foreach ($grades as $grade) {
            $examGroup = ExamGroup::with('exam_violations')
                ->where('exam_id', $grade->exam_id)
                ->where('student_id', $grade->student_id)
                ->first();
            
            $grade->exam_group = $examGroup;
            $grade->violation_count = $examGroup ? ($examGroup->violation_count ?? 0) : 0;
        }

        // --- Kecermatan sessions ---
        $kecermatanQuery = KecermatanSession::with(['student:id,name,email', 'exam:id,title'])
            ->where('status', 'completed');

        // Filter kecermatan by student search
        if ($request->filled('search')) {
            $searchLower = mb_strtolower(trim($request->search));
            $kecermatanQuery->whereHas('student', function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchLower . '%']);
            });
        }
        if ($request->filled('student_id')) {
            $kecermatanQuery->where('student_id', $request->student_id);
        }

        $kecermatanSessions = $kecermatanQuery
            ->orderBy('end_time', 'desc')
            ->paginate($perPage, ['*'], 'kec_page')
            ->withQueryString()
            ->through(fn ($s) => [
                'id'                  => $s->id,
                'kecermatan_exam_id'  => $s->kecermatan_exam_id,
                'student_name'        => $s->student->name ?? '-',
                'student_email'       => $s->student->email ?? '-',
                'exam_title'          => $s->exam->title ?? '-',
                'exam_type'           => strtoupper($s->exam_type ?? 'GAMBAR'),
                'total_correct'       => $s->total_correct ?? 0,
                'total_wrong'         => $s->total_wrong ?? 0,
                'total_score'         => $s->total_score ?? 0,
                'violation_count'     => $s->violation_count ?? 0,
                'finished_at'         => $s->end_time ? $s->end_time->format('d M Y H:i') : '-',
            ]);

        return inertia('Admin/Reports/Index', [
            'exams'               => $exams,
            'lessons'             => $lessons,
            'students'            => $students,
            'grades'              => $grades,
            'kecermatanSessions'  => $kecermatanSessions,
            'statistics'          => null,
            'filters'             => $request->only([
                'exam_id',
                'student_id',
                'lesson_id',
                'date_from',
                'date_to',
                'grade_min',
                'grade_max',
                'status',
                'search',
                'sort_by',
                'sort_order'
            ])
        ]);
    }


    public function filter(Request $request)
    {
        // Build query dengan multiple filters
        $query = Grade::with(['student', 'exam.lesson'])
            ->where('status', 'completed'); // Only completed grades

        // Filter by exam
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        // Filter by student
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by lesson/mapel
        if ($request->filled('lesson_id')) {
            $query->whereHas('exam', function ($q) use ($request) {
                $q->where('lesson_id', $request->lesson_id);
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by grade range
        if ($request->filled('grade_min')) {
            $query->where('grade', '>=', $request->grade_min);
        }
        if ($request->filled('grade_max')) {
            $query->where('grade', '<=', $request->grade_max);
        }

        // Filter by status (passed/failed)
        // Kepribadian: passing grade 50%, Lainnya: passing grade 70%
        if ($request->filled('status')) {
            if ($request->status === 'passed') {
                $query->where(function ($q) {
                    // Untuk ujian kepribadian: >= 50
                    $q->whereHas('exam.lesson', function ($lq) {
                        $lq->whereRaw('LOWER(name) LIKE ?', ['%kepribadian%']);
                    })->where('grade', '>=', 50)
                    // Untuk ujian lainnya: >= 70
                    ->orWhere(function ($oq) {
                        $oq->whereHas('exam.lesson', function ($lq) {
                            $lq->whereRaw('LOWER(name) NOT LIKE ?', ['%kepribadian%']);
                        })->where('grade', '>=', 70);
                    });
                });
            } elseif ($request->status === 'failed') {
                $query->where(function ($q) {
                    // Untuk ujian kepribadian: < 50
                    $q->whereHas('exam.lesson', function ($lq) {
                        $lq->whereRaw('LOWER(name) LIKE ?', ['%kepribadian%']);
                    })->where('grade', '<', 50)
                    // Untuk ujian lainnya: < 70
                    ->orWhere(function ($oq) {
                        $oq->whereHas('exam.lesson', function ($lq) {
                            $lq->whereRaw('LOWER(name) NOT LIKE ?', ['%kepribadian%']);
                        })->where('grade', '<', 70);
                    });
                });
            }
        }

        // Search by student name or exam title
        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLower = mb_strtolower($search);

            $query->where(function ($q) use ($searchLower) {
                $q->whereHas('student', function ($sq) use ($searchLower) {
                    $sq->whereRaw('LOWER(name) LIKE ?', ['%' . $searchLower . '%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $searchLower . '%']);
                })
                    ->orWhereHas('exam', function ($eq) use ($searchLower) {
                        $eq->whereRaw('LOWER(title) LIKE ?', ['%' . $searchLower . '%']);
                    });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'student_name') {
            $query->join('students', 'grades.student_id', '=', 'students.id')
                ->orderBy('students.name', $sortOrder)
                ->select('grades.*');
        } elseif ($sortBy === 'exam_title') {
            $query->join('exams', 'grades.exam_id', '=', 'exams.id')
                ->orderBy('exams.title', $sortOrder)
                ->select('grades.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        //get per_page from request, default to 10, max 100
        $perPage = request()->get('per_page', 10);
        $perPage = min(max((int) $perPage, 5), 100);

        $grades = $query->paginate($perPage)->withQueryString();

        // Manual load exam groups dengan violations untuk setiap grade
        foreach ($grades as $grade) {
            $examGroup = ExamGroup::with('exam_violations')
                ->where('exam_id', $grade->exam_id)
                ->where('student_id', $grade->student_id)
                ->first();
            
            $grade->exam_group = $examGroup;
            $grade->violation_count = $examGroup ? ($examGroup->violation_count ?? 0) : 0;
        }

        // Calculate statistics
        $statistics = null;

        // Get filter options
        $exams = Exam::with('lesson')->orderBy('created_at', 'desc')->get();
        $lessons = Lesson::orderBy('name')->get();
        $students = Student::orderBy('name')->get();

        // Check if there's personality exam
        $isPersonality = false;
        if ($request->filled('exam_id')) {
            $exam = Exam::find($request->exam_id);
            $isPersonality = $exam ? $exam->isPersonality() : false;
        }

        return inertia('Admin/Reports/Index', [
            'exams' => $exams,
            'lessons' => $lessons,
            'students' => $students,
            'grades' => $grades,
            'statistics' => $statistics,
            'isPersonality' => $isPersonality,
            'filters' => $request->only([
                'exam_id',
                'student_id',
                'lesson_id',
                'date_from',
                'date_to',
                'grade_min',
                'grade_max',
                'status',
                'search',
                'sort_by',
                'sort_order'
            ])
        ]);
    }

    private function calculateStatistics($grades)
    {
        if ($grades->isEmpty()) {
            return null;
        }

        $scores = $grades->pluck('grade')->filter()->values();

        if ($scores->isEmpty()) {
            return null;
        }

        $sorted = $scores->sort()->values();
        $count = $sorted->count();

        return [
            'total_attempts' => $count,
            'average' => round($scores->average(), 2),
            'highest' => $sorted->max(),
            'lowest' => $sorted->min(),
            'median' => $count % 2 === 0
                ? round(($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2, 2)
                : $sorted[floor($count / 2)],
            'passed' => $grades->filter(fn($g) => $g->grade >= 70)->count(),
            'failed' => $grades->filter(fn($g) => $g->grade < 70)->count(),
            'pass_rate' => round(($grades->filter(fn($g) => $g->grade >= 70)->count() / $count) * 100, 2),
            'grade_distribution' => [
                'A (90-100)' => $grades->filter(fn($g) => $g->grade >= 90)->count(),
                'B (80-89)' => $grades->filter(fn($g) => $g->grade >= 80 && $g->grade < 90)->count(),
                'C (70-79)' => $grades->filter(fn($g) => $g->grade >= 70 && $g->grade < 80)->count(),
                'D (60-69)' => $grades->filter(fn($g) => $g->grade >= 60 && $g->grade < 70)->count(),
                'E (<60)' => $grades->filter(fn($g) => $g->grade < 60)->count(),
            ],
            'top_students' => $grades->sortByDesc('grade')->take(10)->values(),
            'recent_exams' => $grades->sortByDesc('created_at')->take(10)->values(),
        ];
    }

    public function export(Request $request)
    {
        // Build same query as filter
        $query = Grade::with(['student', 'exam.lesson']);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('lesson_id')) {
            $query->whereHas('exam', function ($q) use ($request) {
                $q->where('lesson_id', $request->lesson_id);
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('grade_min')) {
            $query->where('grade', '>=', $request->grade_min);
        }
        if ($request->filled('grade_max')) {
            $query->where('grade', '<=', $request->grade_max);
        }
        if ($request->filled('status')) {
            if ($request->status === 'passed') {
                $query->where(function ($q) {
                    // Untuk ujian kepribadian: >= 50
                    $q->whereHas('exam.lesson', function ($lq) {
                        $lq->whereRaw('LOWER(name) LIKE ?', ['%kepribadian%']);
                    })->where('grade', '>=', 50)
                    // Untuk ujian lainnya: >= 70
                    ->orWhere(function ($oq) {
                        $oq->whereHas('exam.lesson', function ($lq) {
                            $lq->whereRaw('LOWER(name) NOT LIKE ?', ['%kepribadian%']);
                        })->where('grade', '>=', 70);
                    });
                });
            } elseif ($request->status === 'failed') {
                $query->where(function ($q) {
                    // Untuk ujian kepribadian: < 50
                    $q->whereHas('exam.lesson', function ($lq) {
                        $lq->whereRaw('LOWER(name) LIKE ?', ['%kepribadian%']);
                    })->where('grade', '<', 50)
                    // Untuk ujian lainnya: < 70
                    ->orWhere(function ($oq) {
                        $oq->whereHas('exam.lesson', function ($lq) {
                            $lq->whereRaw('LOWER(name) NOT LIKE ?', ['%kepribadian%']);
                        })->where('grade', '<', 70);
                    });
                });
            }
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLower = mb_strtolower($search);

            $query->where(function ($q) use ($searchLower) {
                $q->whereHas('student', function ($sq) use ($searchLower) {
                    $sq->whereRaw('LOWER(name) LIKE ?', ['%' . $searchLower . '%']);
                })
                    ->orWhereHas('exam', function ($eq) use ($searchLower) {
                        $eq->whereRaw('LOWER(title) LIKE ?', ['%' . $searchLower . '%']);
                    });
            });
        }

        $grades = $query->get();
        
        // Manual load exam groups dengan violations untuk setiap grade
        foreach ($grades as $grade) {
            $examGroup = ExamGroup::with('exam_violations')
                ->where('exam_id', $grade->exam_id)
                ->where('student_id', $grade->student_id)
                ->first();
            
            $grade->exam_group = $examGroup;
            $grade->violation_count = $examGroup ? ($examGroup->violation_count ?? 0) : 0;
        }

        $isPersonality = false;
        if ($request->filled('exam_id')) {
            $exam = Exam::find($request->exam_id);
            $isPersonality = $exam ? $exam->isPersonality() : false;
        }

        $filename = 'Laporan_Nilai_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new GradesExport($grades, $isPersonality), $filename);
    }

    public function print($id)
    {
        $grade = Grade::with('student', 'exam.lesson')->findOrFail($id);

        $examGroup = ExamGroup::with('exam_violations')
            ->where('student_id', $grade->student_id)
            ->where('exam_id', $grade->exam_id)
            ->first();

        return view('admin.reports.print', [
            'grade' => $grade,
            'examGroup' => $examGroup
        ]);
    }

    public function printStudent($studentId)
    {
        $student = Student::findOrFail($studentId);
        $grades = Grade::with('exam.lesson')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($grades as $grade) {
            $grade->exam_group = ExamGroup::with('exam_violations')
                ->where('student_id', $grade->student_id)
                ->where('exam_id', $grade->exam_id)
                ->first();
        }

        return view('admin.reports.print_student', [
            'student' => $student,
            'grades' => $grades
        ]);
    }

    public function students(Request $request)
    {
        $query = Student::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        //get per_page from request, default to 10, max 100
        $perPage = request()->get('per_page', 10);
        $perPage = min(max((int) $perPage, 5), 100);

        $students = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        return inertia('Admin/Reports/Students', [
            'students' => $students,
            'filters' => $request->only(['search'])
        ]);
    }

    public function selectExams(Request $request)
    {
        $studentIds = array_filter(explode(',', $request->get('students', '')));

        if (empty($studentIds)) {
            return redirect()->route('admin.reports.students');
        }

        $students = Student::whereIn('id', $studentIds)->orderBy('name')->get();

        // Ujian reguler yang pernah dikerjakan siswa terpilih
        $exams = Exam::with('lesson')
            ->whereIn('id', Grade::whereIn('student_id', $studentIds)->pluck('exam_id')->unique())
            ->orderBy('created_at', 'desc')
            ->get();

        // Ujian kecermatan yang pernah dikerjakan siswa terpilih
        $kecermatanExams = \App\Models\KecermatanExam::whereIn(
            'id',
            \App\Models\KecermatanSession::whereIn('student_id', $studentIds)
                ->where('status', 'completed')
                ->pluck('kecermatan_exam_id')
                ->unique()
        )->orderBy('created_at', 'desc')->get();

        return inertia('Admin/Reports/SelectExams', [
            'students'        => $students,
            'studentIds'      => implode(',', $studentIds),
            'exams'           => $exams,
            'kecermatanExams' => $kecermatanExams,
        ]);
    }

    public function bulkPrintStudent(Request $request)
    {
        $studentIds = array_filter(explode(',', $request->get('students', '')));
        $examIds = $request->filled('exams')
            ? array_filter(explode(',', $request->get('exams', '')))
            : null;

        if (empty($studentIds)) {
            return back()->with('error', 'Tidak ada siswa yang dipilih.');
        }

        $studentsData = [];

        $students = Student::whereIn('id', $studentIds)->orderBy('name')->get();

        foreach ($students as $student) {
            $gradesQuery = Grade::with('exam.lesson')
                ->where('student_id', $student->id);

            if ($examIds) {
                $gradesQuery->whereIn('exam_id', $examIds);
            }

            $grades = $gradesQuery->orderBy('created_at', 'desc')->get();

            foreach ($grades as $grade) {
                $grade->exam_group = \App\Models\ExamGroup::with('exam_violations')
                    ->where('student_id', $grade->student_id)
                    ->where('exam_id', $grade->exam_id)
                    ->first();
            }

            $studentsData[] = [
                'student' => $student,
                'grades' => $grades
            ];
        }

        return view('admin.reports.bulk_print_student', [
            'studentsData' => $studentsData
        ]);
    }
}
