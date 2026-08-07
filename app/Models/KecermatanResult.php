<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KecermatanResult extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_results';

    protected $fillable = [
        'session_id',
        'column_number',
        'total_questions',
        'correct_count',
        'wrong_count',
        'unanswered_count',
        'time_spent',
    ];

    protected $casts = [
        'column_number' => 'integer',
        'total_questions' => 'integer',
        'correct_count' => 'integer',
        'wrong_count' => 'integer',
        'unanswered_count' => 'integer',
        'time_spent' => 'integer',
    ];

    /**
     * Relasi ke session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(KecermatanSession::class, 'session_id');
    }

    /**
     * Get accuracy percentage
     */
    public function getAccuracyAttribute(): float
    {
        if ($this->total_questions == 0) {
            return 0;
        }
        return ($this->correct_count / $this->total_questions) * 100;
    }
}
