<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KecermatanQuestion extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_questions';

    protected $fillable = [
        'session_id',
        'master_question_id',
        'column_number',
        'question_number',
        'shuffled_order',
        'reference_sequence',
        'question_sequence',
        'missing_position',
        'missing_item',
        'correct_answer',
        'student_answer',
        'is_correct',
        'time_spent',
        'answered_at',
    ];

    protected $casts = [
        'reference_sequence' => 'array',
        'question_sequence' => 'array',
        'column_number' => 'integer',
        'question_number' => 'integer',
        'shuffled_order' => 'integer',
        'missing_position' => 'integer',
        'is_correct' => 'boolean',
        'time_spent' => 'integer',
        'answered_at' => 'datetime',
    ];

    /**
     * Relasi ke session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(KecermatanSession::class, 'session_id');
    }

    /**
     * Relasi ke master question
     */
    public function masterQuestion(): BelongsTo
    {
        return $this->belongsTo(KecermatanMasterQuestion::class, 'master_question_id');
    }

    /**
     * Check if question is answered
     */
    public function isAnswered(): bool
    {
        return $this->student_answer !== null;
    }
}
