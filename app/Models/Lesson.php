<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $appends = ['thumbnail_url'];

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category',
        'thumbnail',
        'is_active',
        'order'
    ];

    /**
     * casts
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail
            ? '/storage/'.ltrim($this->thumbnail, '/')
            : null;
    }

    /**
     * Scope untuk mapel psikologi
     */
    public function scopePsikologi($query)
    {
        return $query->where('category', 'psikologi');
    }

    /**
     * Scope untuk mapel akademik
     */
    public function scopeAkademik($query)
    {
        return $query->where('category', 'akademik');
    }

    /**
     * Scope untuk mapel aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
