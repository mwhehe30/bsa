<?php

namespace App\Services;

use App\Models\ExamGroup;
use App\Models\KecermatanResult;
use App\Models\KecermatanSession;

class KecermatanSessionFinalizer
{
    /**
     * Finalisasi sesi kecermatan: hitung hasil 10 kolom, tandai selesai, dan
     * keluarkan dari isolir bila tertaut ke ujian reguler.
     *
     * Idempotent: sesi yang sudah completed tidak diubah lagi.
     *
     * @return bool true jika sesi baru saja difinalisasi
     */
    public function finalize(KecermatanSession $session, bool $currentColumnCompleted = false): bool
    {
        if ($session->status === 'completed') {
            return false;
        }

        $generator = app(KecermatanQuestionGenerator::class);

        // Pastikan setiap kolom 1-10 memiliki hasil yang akurat. Kolom yang
        // finalisasinya gagal di background (mis. koneksi putus) tetap dihitung
        // di sini agar total hasil akhir selalu lengkap. updateOrCreate dipakai
        // agar aman terhadap request ganda.
        $currentColumn = (int) $session->current_column;
        $activeColumnDuration = $session->column_start_time
            ? min(60, max(0, now()->timestamp - $session->column_start_time->timestamp))
            : 0;

        for ($col = 1; $col <= 10; $col++) {
            $result = $generator->calculateColumnResult($session->id, $col);

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
            'is_blocked' => false,
            'total_correct' => $results->sum('correct_count'),
            'total_wrong' => $results->sum('wrong_count'),
            'total_unanswered' => $results->sum('unanswered_count'),
            'total_score' => $results->sum('correct_count'),
        ]);

        // Buka blokir di exam_groups setelah selesai (keluarkan dari isolir)
        $kecermatanExam = $session->exam;
        if ($kecermatanExam && $kecermatanExam->exam_id) {
            ExamGroup::where('student_id', $session->student_id)
                ->where('exam_id', $kecermatanExam->exam_id)
                ->where('is_blocked', true)
                ->update(['is_blocked' => false]);
        }

        return true;
    }
}
