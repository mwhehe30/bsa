<?php

namespace App\Http\Controllers\Student;

use App\Models\ExamGroup;
use App\Models\ExamViolation;
use App\Models\Exam;
use App\Models\Grade;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Events\StudentBlockStatusChanged;

class ExamSecurityController extends Controller
{
    public function logViolation(Request $request)
    {
        $request->validate([
            'exam_group_id' => 'required|exists:exam_groups,id',
            'exam_id' => 'required|exists:exams,id',
            'violation_type' => 'required|in:tab_switch',
        ]);

        try {
            // Use transaction for atomic operation
            \DB::beginTransaction();

            // Lock exam group to prevent race conditions
            $examGroup = ExamGroup::where('id', $request->exam_group_id)
                ->lockForUpdate()
                ->first();

            if (!$examGroup) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Data ujian tidak ditemukan.',
                ], 404);
            }

            // Cek kepemilikan: siswa hanya boleh mencatat pelanggaran untuk
            // exam_group miliknya sendiri (mencegah siswa lain mengganggu).
            if ($examGroup->student_id !== auth()->guard('student')->id()) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            // Cek apakah sudah diblokir
            if ($examGroup->is_blocked) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa sudah diblokir.',
                    'is_blocked' => true,
                    'violation_count' => $examGroup->violation_count,
                    'violations' => $examGroup->exam_violations()->orderBy('violation_time', 'desc')->get(['violation_type', 'violation_time']),
                    'should_auto_submit' => $examGroup->violation_count >= 3,
                ], 403);
            }

            // Catat pelanggaran tab switch
            ExamViolation::create([
                'exam_group_id' => $examGroup->id,
                'exam_id' => $request->exam_id,
                'violation_type' => 'tab_switch',
                'violation_time' => Carbon::now(),
            ]);

            // Tambah counter pelanggaran
            $examGroup->increment('violation_count');
            $examGroup->refresh();

            // LANGSUNG BLOKIR SETIAP TAB SWITCH
            $examGroup->update(['is_blocked' => true]);

            // Broadcast event for real-time update
            try {
                broadcast(new StudentBlockStatusChanged(
                    $examGroup->student_id,
                    true,
                    $examGroup->id,
                    $examGroup->violation_count
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to broadcast StudentBlockStatusChanged: ' . $e->getMessage());
            }

            \DB::commit();

            // Tentukan apakah harus auto-submit (pelanggaran ke-3)
            $shouldAutoSubmit = $examGroup->violation_count >= 3;
            
            $message = $shouldAutoSubmit 
                ? 'Anda diblokir karena 3x pindah tab. Ujian akan otomatis disubmit.'
                : "Anda diblokir karena pindah tab. Pelanggaran {$examGroup->violation_count} dari 3. Hubungi pengawas untuk membuka blokir.";

            return response()->json([
                'success' => false,
                'message' => $message,
                'is_blocked' => true,
                'violation_count' => $examGroup->violation_count,
                'violations' => $examGroup->exam_violations()->orderBy('violation_time', 'desc')->get(['violation_type', 'violation_time']),
                'should_auto_submit' => $shouldAutoSubmit,
            ], 403);

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Error logViolation: ' . $e->getMessage(), [
                'exam_group_id' => $request->exam_group_id,
                'exam_id' => $request->exam_id,
                'violation_type' => $request->violation_type,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencatat pelanggaran.',
            ], 500);
        }
    }

    public function checkStatus(Request $request)
    {
        $request->validate([
            'exam_group_id' => 'required|exists:exam_groups,id',
        ]);

        $examGroup = ExamGroup::findOrFail($request->exam_group_id);

        // Cek kepemilikan: hanya siswa pemilik exam_group yang boleh membaca.
        if ($examGroup->student_id !== auth()->guard('student')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Calculate remaining time (same logic as ExamController::show)
        $remainingSeconds = 0;
        $grade = Grade::where('student_id', $examGroup->student_id)
                    ->where('exam_id', $examGroup->exam_id)
                    ->where('status', 'in_progress')
                    ->first();
        
        if ($grade && $grade->start_time) {
            $exam = Exam::find($examGroup->exam_id);
            if ($exam) {
                $startTime = Carbon::parse($grade->start_time);
                $totalDurationSeconds = $exam->duration * 60;
                $elapsedSeconds = Carbon::now()->diffInSeconds($startTime);
                $remainingSeconds = max(0, $totalDurationSeconds - $elapsedSeconds);
            }
        }

        return response()->json([
            'is_blocked' => $examGroup->is_blocked,
            'violation_count' => $examGroup->violation_count,
            'violations' => $examGroup->exam_violations()->orderBy('violation_time', 'desc')->get(['violation_type', 'violation_time']),
            'remaining_seconds' => $remainingSeconds,
        ]);
    }
}
