<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KecermatanViolation extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_violations';

    protected $fillable = [
        'session_id',
        'violation_type',
        'violation_time',
        'column_number',
        'question_number',
        'notes',
    ];

    protected $casts = [
        'violation_time' => 'datetime',
        'column_number' => 'integer',
        'question_number' => 'integer',
    ];

    /**
     * Relasi ke session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(KecermatanSession::class, 'session_id');
    }

    /**
     * Get violation type label
     */
    public function getViolationLabelAttribute(): string
    {
        return match($this->violation_type) {
            'exit_fullscreen' => 'Keluar Fullscreen',
            'tab_switch' => 'Pindah Tab',
            'browser_blur' => 'Browser Kehilangan Fokus',
            default => $this->violation_type
        };
    }
}
