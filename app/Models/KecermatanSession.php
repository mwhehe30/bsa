<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KecermatanSession extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_sessions';

    protected $fillable = [
        'kecermatan_exam_id',
        'student_id',
        'exam_type',
        'current_column',
        'current_question',
        'status',
        'is_blocked',
        'violation_count',
        'start_time',
        'column_start_time',
        'end_time',
        'total_score',
        'total_correct',
        'total_wrong',
        'total_unanswered',
    ];

    protected $casts = [
        'current_column' => 'integer',
        'current_question' => 'integer',
        'is_blocked' => 'boolean',
        'violation_count' => 'integer',
        'total_score' => 'integer',
        'total_correct' => 'integer',
        'total_wrong' => 'integer',
        'total_unanswered' => 'integer',
        'start_time' => 'datetime',
        'column_start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Relasi ke exam
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(KecermatanExam::class, 'kecermatan_exam_id');
    }

    /**
     * Relasi ke student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Relasi ke questions (500 soal untuk session ini)
     */
    public function questions(): HasMany
    {
        return $this->hasMany(KecermatanQuestion::class, 'session_id');
    }

    /**
     * Relasi ke results (hasil per kolom)
     */
    public function results(): HasMany
    {
        return $this->hasMany(KecermatanResult::class, 'session_id');
    }

    /**
     * Relasi ke violations
     */
    public function violations(): HasMany
    {
        return $this->hasMany(KecermatanViolation::class, 'session_id');
    }

    /**
     * Get current question object
     */
    public function getCurrentQuestion()
    {
        return $this->questions()
            ->where('column_number', $this->current_column)
            ->where('question_number', $this->current_question)
            ->first();
    }

    /**
     * Get soal-soal di kolom tertentu
     */
    public function getColumnQuestions(int $columnNumber)
    {
        return $this->questions()
            ->where('column_number', $columnNumber)
            ->orderBy('shuffled_order')
            ->get();
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalQuestions = 500; // 10 kolom x 50 soal
        $answeredQuestions = $this->questions()->whereNotNull('student_answer')->count();
        return ($answeredQuestions / $totalQuestions) * 100;
    }

    /**
     * Get duration in seconds
     */
    public function getDurationInSecondsAttribute(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }
        return $this->start_time->diffInSeconds($this->end_time);
    }
}
