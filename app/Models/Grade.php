<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'attempt_number',
        'duration',
        'start_time',
        'end_time',
        'total_correct',
        'grade',
        'total_points',
        'max_points',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Method helper untuk mendapatkan exam group dengan violations
    public function getExamGroupWithViolations()
    {
        return ExamGroup::with('exam_violations')
            ->where('exam_id', $this->exam_id)
            ->where('student_id', $this->student_id)
            ->first();
    }

    public function isCompleted()
    {
        return $this->end_time !== null;
    }

    public function getPercentageAttribute()
    {
        if ($this->max_points && $this->max_points > 0) {
            return round(($this->total_points / $this->max_points) * 100, 2);
        }
        return 0;
    }

    /**
     * Hitung total_correct / total_points / grade dari tabel answers untuk
     * attempt ini. Dipakai bersama oleh endExam, autoSubmitExam, dan
     * auto-complete dashboard agar aturan penilaian tidak terduplikasi.
     */
    public function finalizeFromAnswers(Exam $exam): void
    {
        if ($exam->isPersonality()) {
            $totalPoints = Answer::where('grade_id', $this->id)
                ->where('answer', '!=', 0)
                ->sum('point');

            $maxPoints = Question::where('exam_id', $exam->id)
                ->get()
                ->sum(fn ($q) => $q->getMaxPoint());

            $this->total_points = $totalPoints;
            $this->max_points = $maxPoints;
            $this->grade = $maxPoints > 0 ? round(($totalPoints / $maxPoints) * 100, 2) : 0;
        } else {
            $countCorrect = Answer::where('grade_id', $this->id)
                ->where('is_correct', 'Y')
                ->count();

            $countQuestion = Answer::where('grade_id', $this->id)->count();

            $this->total_correct = $countCorrect;
            $this->grade = $countQuestion > 0 ? round($countCorrect / $countQuestion * 100, 2) : 0;
        }
    }
}
