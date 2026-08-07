<?php

namespace App\Console\Commands;

use App\Models\KecermatanSession;
use App\Models\KecermatanQuestion;
use App\Models\KecermatanResult;
use App\Services\KecermatanQuestionGenerator;
use Illuminate\Console\Command;

class TestKecermatanFlow extends Command
{
    protected $signature = 'test:kecermatan-flow {session_id}';
    protected $description = 'Test kecermatan flow: validate data from submit to result';

    protected KecermatanQuestionGenerator $generator;

    public function __construct(KecermatanQuestionGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    public function handle()
    {
        $sessionId = $this->argument('session_id');
        
        $session = KecermatanSession::find($sessionId);
        
        if (!$session) {
            $this->error("Session ID {$sessionId} tidak ditemukan!");
            return 1;
        }

        $this->info("=== Testing Kecermatan Flow ===");
        $this->info("Session ID: {$session->id}");
        $this->info("Student ID: {$session->student_id}");
        $this->info("Exam Type: {$session->exam_type}");
        $this->info("Status: {$session->status}");
        $this->newLine();

        // Test 1: Cek total questions di database
        $this->info("[TEST 1] Checking Questions Data...");
        $totalQuestions = KecermatanQuestion::where('session_id', $sessionId)->count();
        $this->info("✓ Total questions in database: {$totalQuestions}");
        
        if ($totalQuestions !== 500) {
            $this->warn("⚠ Expected 500 questions (10 columns x 50), found {$totalQuestions}");
        }
        $this->newLine();

        // Test 2: Cek data per kolom
        $this->info("[TEST 2] Checking Data Per Column...");
        $this->table(
            ['Column', 'Total Q', 'Answered', 'Correct', 'Wrong', 'Unanswered', 'Time Spent'],
            collect(range(1, 10))->map(function ($col) use ($sessionId) {
                $questions = KecermatanQuestion::where('session_id', $sessionId)
                    ->where('column_number', $col)
                    ->get();
                
                $answered = $questions->whereNotNull('student_answer')->count();
                $correct = $questions->where('is_correct', true)->count();
                $wrong = $questions->where('is_correct', false)->whereNotNull('student_answer')->count();
                $unanswered = $questions->whereNull('student_answer')->count();
                $timeSpent = $questions->sum('time_spent');
                
                return [
                    $col,
                    $questions->count(),
                    $answered,
                    $correct,
                    $wrong,
                    $unanswered,
                    $timeSpent . 's',
                ];
            })
        );
        $this->newLine();

        // Test 3: Validate student_answer dan is_correct consistency
        $this->info("[TEST 3] Validating Answer Consistency...");
        $inconsistent = KecermatanQuestion::where('session_id', $sessionId)
            ->whereNotNull('student_answer')
            ->get()
            ->filter(function ($q) {
                $expectedCorrect = $q->student_answer === $q->correct_answer;
                return $q->is_correct !== $expectedCorrect;
            });
        
        if ($inconsistent->count() > 0) {
            $this->error("✗ Found {$inconsistent->count()} inconsistent answers!");
            $this->table(
                ['Question ID', 'Column', 'Question #', 'Student Answer', 'Correct Answer', 'is_correct Flag'],
                $inconsistent->map(fn($q) => [
                    $q->id,
                    $q->column_number,
                    $q->question_number,
                    $q->student_answer,
                    $q->correct_answer,
                    $q->is_correct ? 'TRUE' : 'FALSE',
                ])
            );
        } else {
            $this->info("✓ All answers are consistent!");
        }
        $this->newLine();

        // Test 4: Test calculateColumnResult untuk setiap kolom
        $this->info("[TEST 4] Testing calculateColumnResult()...");
        foreach (range(1, 10) as $col) {
            $result = $this->generator->calculateColumnResult($sessionId, $col);
            
            $expected = KecermatanQuestion::where('session_id', $sessionId)
                ->where('column_number', $col)
                ->get();
            
            $expectedCorrect = $expected->where('is_correct', true)->count();
            $expectedWrong = $expected->where('is_correct', false)->whereNotNull('student_answer')->count();
            $expectedUnanswered = $expected->whereNull('student_answer')->count();
            
            $match = 
                $result['correct_count'] === $expectedCorrect &&
                $result['wrong_count'] === $expectedWrong &&
                $result['unanswered_count'] === $expectedUnanswered;
            
            $status = $match ? '✓' : '✗';
            $this->line("{$status} Column {$col}: Correct={$result['correct_count']}, Wrong={$result['wrong_count']}, Unanswered={$result['unanswered_count']}");
            
            if (!$match) {
                $this->warn("  Expected: Correct={$expectedCorrect}, Wrong={$expectedWrong}, Unanswered={$expectedUnanswered}");
            }
        }
        $this->newLine();

        // Test 5: Cek kecermatan_results table
        $this->info("[TEST 5] Checking kecermatan_results Table...");
        $results = KecermatanResult::where('session_id', $sessionId)
            ->orderBy('column_number')
            ->get();
        
        if ($results->isEmpty()) {
            $this->warn("⚠ No results found in kecermatan_results table!");
            $this->info("  This is normal if session is still in_progress.");
        } else {
            $this->info("✓ Found {$results->count()} result records");
            $this->table(
                ['Column', 'Correct', 'Wrong', 'Unanswered', 'Time Spent'],
                $results->map(fn($r) => [
                    $r->column_number,
                    $r->correct_count,
                    $r->wrong_count,
                    $r->unanswered_count,
                    $r->time_spent . 's',
                ])
            );
        }
        $this->newLine();

        // Test 6: Validate session totals
        $this->info("[TEST 6] Validating Session Totals...");
        if ($session->status === 'completed') {
            $expectedTotalCorrect = $results->sum('correct_count');
            $expectedTotalWrong = $results->sum('wrong_count');
            $expectedTotalUnanswered = $results->sum('unanswered_count');
            
            $match = 
                $session->total_correct === $expectedTotalCorrect &&
                $session->total_wrong === $expectedTotalWrong &&
                $session->total_unanswered === $expectedTotalUnanswered;
            
            if ($match) {
                $this->info("✓ Session totals match results table!");
                $this->info("  Total Correct: {$session->total_correct}");
                $this->info("  Total Wrong: {$session->total_wrong}");
                $this->info("  Total Unanswered: {$session->total_unanswered}");
                $this->info("  Total Score: {$session->total_score}");
            } else {
                $this->error("✗ Session totals DO NOT match results table!");
                $this->warn("  Session: Correct={$session->total_correct}, Wrong={$session->total_wrong}, Unanswered={$session->total_unanswered}");
                $this->warn("  Expected: Correct={$expectedTotalCorrect}, Wrong={$expectedTotalWrong}, Unanswered={$expectedTotalUnanswered}");
            }
        } else {
            $this->info("  Session status: {$session->status} (not completed yet)");
        }
        $this->newLine();

        // Test 7: Check for missing columns in results
        if ($session->status === 'completed') {
            $this->info("[TEST 7] Checking Missing Columns in Results...");
            $missingColumns = collect(range(1, 10))->diff($results->pluck('column_number'));
            
            if ($missingColumns->isEmpty()) {
                $this->info("✓ All 10 columns have results!");
            } else {
                $this->error("✗ Missing results for columns: " . $missingColumns->implode(', '));
            }
        }
        $this->newLine();

        // Summary
        $this->info("=== Test Summary ===");
        if ($session->status === 'completed') {
            $allPassed = 
                $totalQuestions === 500 &&
                $inconsistent->count() === 0 &&
                $results->count() === 10 &&
                $session->total_correct === $results->sum('correct_count');
            
            if ($allPassed) {
                $this->info("✓ ALL TESTS PASSED! Data is valid.");
                return 0;
            } else {
                $this->error("✗ SOME TESTS FAILED! Please check the output above.");
                return 1;
            }
        } else {
            $this->info("⚠ Session is not completed yet. Cannot validate full flow.");
            return 0;
        }
    }
}
