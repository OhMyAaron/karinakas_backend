<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Galeri extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'galeri';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'kegiatan_id',
        'judul',
        'nama_foto',
        'deskripsi',
        'gambar_url',
        'kategori',
        'tanggal',
        'tipe',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Relationships
     */
    
    // Belongs to Kegiatan
    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }

    /**
     * Helper Methods
     */
    public function isFoto(): bool
    {
        return $this->tipe === 'foto';
    }

    public function isVideo(): bool
    {
        return $this->tipe === 'video';
    }

    /**
     * Scopes
     */
    public function scopeFoto($query)
    {
        return $query->where('tipe', 'foto');
    }

    public function scopeVideo($query)
    {
        return $query->where('tipe', 'video');
    }

    public function scopeByKegiatan($query, $kegiatanId)
    {
        return $query->where('kegiatan_id', $kegiatanId);
    }

    /**
     * Accessor untuk thumbnail (jika video)
     */
    public function getThumbnailAttribute(): ?string
    {
        if ($this->isVideo()) {
            // Logic untuk extract thumbnail dari video URL
            // Misal jika YouTube: extract thumbnail
            if (str_contains($this->gambar_url, 'youtube.com') || str_contains($this->gambar_url, 'youtu.be')) {
                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $this->gambar_url, $matches);
                if (isset($matches[1])) {
                    return "https://img.youtube.com/vi/{$matches[1]}/mqdefault.jpg";
                }
            }
        }
        
        return $this->gambar_url;
    }
}