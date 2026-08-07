<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KecermatanExam extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_exams';

    protected $fillable = [
        'exam_id',
        'title',
        'duration',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
    ];

    /**
     * Relasi ke Exam (ujian biasa)
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Exam::class, 'exam_id');
    }

    /**
     * Relasi ke User (pembuat ujian)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke master questions (2000 soal)
     */
    public function masterQuestions(): HasMany
    {
        return $this->hasMany(KecermatanMasterQuestion::class, 'kecermatan_exam_id');
    }

    /**
     * Relasi ke sessions (siswa yang mengerjakan)
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(KecermatanSession::class, 'kecermatan_exam_id');
    }

    /**
     * Get total peserta yang sudah mengerjakan
     */
    public function getTotalParticipantsAttribute(): int
    {
        return $this->sessions()->count();
    }

    /**
     * Get peserta yang sudah selesai
     */
    public function getTotalCompletedAttribute(): int
    {
        return $this->sessions()->where('status', 'completed')->count();
    }

    /**
     * Get peserta yang sedang mengerjakan
     */
    public function getTotalInProgressAttribute(): int
    {
        return $this->sessions()->where('status', 'in_progress')->count();
    }

    /**
     * Get rata-rata skor
     */
    public function getAverageScoreAttribute(): float
    {
        return $this->sessions()
            ->where('status', 'completed')
            ->avg('total_score') ?? 0;
    }
}
