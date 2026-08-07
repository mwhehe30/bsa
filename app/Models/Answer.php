<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'exam_id',
        'grade_id',
        'question_id',
        'student_id',
        'question_order',
        'answer_order',
        'answer',
        'is_correct',
        'point',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
