<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'lesson_id',
        'duration',
        'description',
        'random_question',
        'random_answer',
        'show_answer',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Relasi ke questions - diurutkan ASC agar nomor urut sesuai ID
     */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('id', 'ASC');
    }

    /**
     * Relasi ke kecermatan exam
     */
    public function kecermatanExam()
    {
        return $this->hasOne(KecermatanExam::class);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->whereHas('lesson', function ($q) use ($category) {
            $q->where('category', $category);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isPersonality()
    {
        if (!$this->lesson || !$this->lesson->name) {
            return false;
        }
        $normalized = strtolower(trim($this->lesson->name));
        return $normalized === 'kepribadian' || strpos($normalized, 'kepribadian ') === 0;
    }

    public function isKecermatan()
    {
        if (!$this->lesson || !$this->lesson->name) {
            return false;
        }
        $normalized = strtolower(trim($this->lesson->name));
        return $normalized === 'kecermatan' || strpos($normalized, 'kecermatan ') === 0;
    }

    public function isMultipleChoice()
    {
        return !$this->isPersonality();
    }
}
