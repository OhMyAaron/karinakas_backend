<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kegiatan';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'judul',
        'deskripsi',
        'lokasi',
        'kategori',
        'gambar_url',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'butuh_relawan',
        'butuh_donasi',
        'kuota_relawan',
        'jumlah_relawan_terdaftar',
        'jenis_relawan',
        'bidang_minat',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'butuh_relawan' => 'boolean',
        'butuh_donasi' => 'boolean',
        'kuota_relawan' => 'integer',
        'jumlah_relawan_terdaftar' => 'integer',
        'jenis_relawan' => 'array',
        'bidang_minat' => 'array',
    ];

    /**
     * Relationships
     */
    
    // One kegiatan has many relawan
    public function relawan()
    {
        return $this->hasMany(Relawan::class, 'kegiatan_id');
    }

    // One kegiatan has many galeri
    public function galeri()
    {
        return $this->hasMany(Galeri::class, 'kegiatan_id');
    }

    // One kegiatan can receive many donasi
    public function donasi()
    {
        return $this->hasMany(Donasi::class, 'kegiatan_id');
    }

    /**
     * Helper Methods
     */
    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    public function isDibatalkan(): bool
    {
        return $this->status === 'dibatalkan';
    }

    public function isKuotaPenuh(): bool
    {
        return $this->jumlah_relawan_terdaftar >= $this->kuota_relawan;
    }

    public function sisaKuota(): int
    {
        return max(0, $this->kuota_relawan - $this->jumlah_relawan_terdaftar);
    }

    /**
     * Scopes
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    public function scopeTersedia($query)
    {
        return $query->where('status', 'aktif')
                     ->whereColumn('jumlah_relawan_terdaftar', '<', 'kuota_relawan');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('tanggal_mulai', '>=', now()->toDateString());
    }
}