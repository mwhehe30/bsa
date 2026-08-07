<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\KecermatanSession;
use App\Models\KecermatanExam;
use App\Models\KecermatanMasterQuestion;

class TestKecermatanSpeed extends Command
{
    protected $signature = 'test:speed';
    protected $description = 'Command description';

    public function handle()
    {
        // Setup dummy data if doesn't exist
        $exam = KecermatanExam::first();
        if (!$exam) {
            $exam = KecermatanExam::create([
                'exam_id' => 1,
                'title' => 'Test',
                'duration' => 600,
                'is_active' => true,
                'created_by' => 1
            ]);
            $generator = new \App\Services\KecermatanQuestionGenerator();
            $generator->generate($exam);
        }
        
        $session = KecermatanSession::firstOrCreate([
            'kecermatan_exam_id' => $exam->id,
            'student_id' => 1,
            'exam_type' => 'huruf',
        ]);
        
        // Clean up
        DB::table('kecermatan_questions')->where('session_id', $session->id)->delete();

        $start = microtime(true);
        
        // NEW FAST METHOD
        $masterQuestions = DB::table('kecermatan_master_questions')
            ->where('kecermatan_exam_id', $exam->id)
            ->where('exam_type', 'huruf')
            ->get();
            
        $inserts = [];
        $now = now();
        
        foreach (range(1, 10) as $col) {
            $colQuestions = $masterQuestions->where('column_number', $col)->shuffle()->values();
            foreach ($colQuestions as $idx => $q) {
                $inserts[] = [
                    'session_id' => $session->id,
                    'master_question_id' => $q->id,
                    'column_number' => $col,
                    'question_number' => $q->question_number,
                    'shuffled_order' => $idx + 1,
                    'reference_sequence' => $q->reference_sequence,
                    'question_sequence' => $q->question_sequence,
                    'missing_position' => $q->missing_position,
                    'missing_item' => $q->missing_item,
                    'correct_answer' => $q->correct_answer,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('kecermatan_questions')->insert($chunk);
        }
        
        $end = microtime(true);
        $this->info("New method took: " . ($end - $start) . " seconds");
    }
}
