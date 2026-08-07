<?php

namespace App\Jobs;

use App\Models\KecermatanExam;
use App\Services\KecermatanQuestionGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateKecermatanQuestions implements ShouldQueue
{
    use Queueable;

    /**
     * Number of seconds the job can run before timing out.
     * Generation takes 10-30 seconds, so give a comfortable margin.
     */
    public int $timeout = 300;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $kecermatanExamId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(KecermatanQuestionGenerator $generator): void
    {
        $exam = KecermatanExam::find($this->kecermatanExamId);

        if (!$exam) {
            Log::warning('GenerateKecermatanQuestions: kecermatan exam tidak ditemukan', [
                'kecermatan_exam_id' => $this->kecermatanExamId,
            ]);
            return;
        }

        // Idempotent & race-safe: hanya generate tipe yang belum ada
        $generator->ensureGenerated($exam);
    }
}
