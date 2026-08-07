<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'exam_id',
        'question',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
        'option_5',
        'answer',
        'points',
        'needs_review',
        'review_notes',
    ];

    protected $casts = [
        'points' => 'array',
        'needs_review' => 'boolean',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function getDefaultPointsAttribute()
    {
        return [
            '1' => 5,
            '2' => 4,
            '3' => 3,
            '4' => 2,
            '5' => 1,
        ];
    }

    public function getPointsAttribute($value)
    {
        if ($value) {
            return is_string($value) ? json_decode($value, true) : $value;
        }
        return $this->getDefaultPointsAttribute();
    }

    public function setPointsAttribute($value)
    {
        $this->attributes['points'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getPoint($optionNumber)
    {
        $points = $this->points;
        return $points[$optionNumber] ?? (6 - $optionNumber);
    }

    public function getMaxPoint()
    {
        $points = $this->points;
        return max($points);
    }

    /**
     * Scope untuk filter soal yang perlu review
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }
}
