<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamViolation extends Model
{
    protected $table = 'exam_violations';

    protected $fillable = [
        'exam_group_id',
        'exam_id',
        'violation_type',
        'violation_time',
        'notes',
    ];

    protected $casts = [
        'violation_time' => 'datetime',
    ];

    public function exam_group()
    {
        return $this->belongsTo(ExamGroup::class, 'exam_group_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
