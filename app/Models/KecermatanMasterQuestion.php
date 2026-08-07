<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KecermatanMasterQuestion extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_master_questions';

    protected $fillable = [
        'kecermatan_exam_id',
        'exam_type',
        'column_number',
        'question_number',
        'reference_sequence',
        'question_sequence',
        'missing_position',
        'missing_item',
        'correct_answer',
    ];

    protected $casts = [
        'reference_sequence' => 'array',
        'question_sequence' => 'array',
        'column_number' => 'integer',
        'question_number' => 'integer',
        'missing_position' => 'integer',
    ];

    /**
     * Relasi ke exam
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(KecermatanExam::class, 'kecermatan_exam_id');
    }

    /**
     * Relasi ke questions (copy untuk siswa)
     */
    public function questions(): HasMany
    {
        return $this->hasMany(KecermatanQuestion::class, 'master_question_id');
    }
}
