<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relawan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'relawan';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'kegiatan_id',
        'jenis',
        'minat',
        'kemampuan_diri',
        'motivasi',
        'nomor_ktp',
        'kebutuhan_khusus',
        'status',
        'tanggal_daftar',
    ];

    protected $casts = [
        'tanggal_daftar' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    // Belongs to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Belongs to Kegiatan
    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }

    /**
     * Helper Methods
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDiterima(): bool
    {
        return $this->status === 'diterima';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }

    public function hasKebutuhanKhusus(): bool
    {
        return !is_null($this->kebutuhan_khusus) && trim($this->kebutuhan_khusus) !== '';
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDiterima($query)
    {
        return $query->where('status', 'diterima');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', 'ditolak');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByKegiatan($query, $kegiatanId)
    {
        return $query->where('kegiatan_id', $kegiatanId);
    }

    public function scopeWithKebutuhanKhusus($query)
    {
        return $query->whereNotNull('kebutuhan_khusus')
                     ->where('kebutuhan_khusus', '!=', '');
    }

    /**
     * Event Listeners (untuk auto-update jumlah_relawan_terdaftar di kegiatan)
     */
    protected static function booted()
    {
        // Ketika relawan langsung dibuat berstatus diterima (seeder / impor data),
        // event updated tidak pernah jalan sehingga counter kegiatan ikut tertinggal
        static::created(function ($relawan) {
            if ($relawan->status === 'diterima' && $relawan->kegiatan) {
                $relawan->kegiatan->increment('jumlah_relawan_terdaftar');
            }
        });

        // Ketika relawan diterima, tambah jumlah_relawan_terdaftar
        static::updated(function ($relawan) {
            if ($relawan->isDirty('status')) {
                $kegiatan = $relawan->kegiatan;
                if (!$kegiatan) return; // relawan umum tanpa kegiatan

                if ($relawan->status === 'diterima' && $relawan->getOriginal('status') !== 'diterima') {
                    // Status berubah menjadi diterima
                    $kegiatan->increment('jumlah_relawan_terdaftar');
                } elseif ($relawan->getOriginal('status') === 'diterima' && $relawan->status !== 'diterima') {
                    // Status berubah dari diterima ke yang lain
                    $kegiatan->decrement('jumlah_relawan_terdaftar');
                }
            }
        });

        // Ketika relawan dihapus dan statusnya diterima, kurangi jumlah
        static::deleted(function ($relawan) {
            if ($relawan->status === 'diterima' && $relawan->kegiatan) {
                $relawan->kegiatan->decrement('jumlah_relawan_terdaftar');
            }
        });
    }
}