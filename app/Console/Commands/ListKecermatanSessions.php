<?php

namespace App\Console\Commands;

use App\Models\KecermatanSession;
use Illuminate\Console\Command;

class ListKecermatanSessions extends Command
{
    protected $signature = 'kecermatan:sessions';
    protected $description = 'List all kecermatan sessions';

    public function handle()
    {
        $sessions = KecermatanSession::with('student')->orderBy('id', 'desc')->take(20)->get();
        
        if ($sessions->isEmpty()) {
            $this->warn('No sessions found.');
            return 0;
        }

        $this->table(
            ['ID', 'Student ID', 'Student Name', 'Type', 'Status', 'Column', 'Score'],
            $sessions->map(fn($s) => [
                $s->id,
                $s->student_id,
                $s->student->name ?? 'N/A',
                $s->exam_type,
                $s->status,
                $s->current_column,
                $s->total_score,
            ])
        );

        return 0;
    }
}
