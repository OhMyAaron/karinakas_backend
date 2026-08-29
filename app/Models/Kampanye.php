<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kampanye extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kampanye';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'judul',
        'konten',
        'ringkasan',
        'gambar_url',
        'penulis',
        'penulis_avatar',
        'tags',
        'waktu_baca',
        'kategori',
        'status',
        'tanggal_publish',
    ];

    protected $casts = [
        'tags' => 'array',
        'tanggal_publish' => 'datetime',
    ];

    /**
     * No relationships - Kampanye is standalone content
     */

    /**
     * Helper Methods
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeRecent($query)
    {
        return $query->where('status', 'published')
                     ->orderBy('tanggal_publish', 'desc');
    }

    /**
     * Mutators
     */
    public function getExcerptAttribute(): string
    {
        return \Str::limit($this->konten, 150);
    }
}