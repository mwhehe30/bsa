<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamGroup extends Model
{
    protected $table = 'exam_groups';

    protected $fillable = [
        'student_id',
        'exam_id',
        'violation_count',
        'is_blocked',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'violation_count' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function exam_violations()
    {
        return $this->hasMany(ExamViolation::class, 'exam_group_id');
    }
}
