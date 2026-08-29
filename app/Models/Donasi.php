<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donasi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'donasi';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'kegiatan_id',
        'jumlah',
        'metode_pembayaran',
        'status_pembayaran',
        'keterangan',
        'tujuan_donasi',
        'biaya_transfer',
        'kode_transaksi',
        'rekening_pengirim',
        'bukti_transfer',
        'anonim',
        'notifikasi_terkirim',
        'tanggal_donasi',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'biaya_transfer' => 'decimal:2',
        'anonim' => 'boolean',
        'notifikasi_terkirim' => 'boolean',
        'tanggal_donasi' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    // Belongs to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Belongs to Kegiatan (nullable)
    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }

    /**
     * Helper Methods
     */
    public function isPending(): bool
    {
        return $this->status_pembayaran === 'pending';
    }

    public function isBerhasil(): bool
    {
        return $this->status_pembayaran === 'berhasil';
    }

    public function isGagal(): bool
    {
        return $this->status_pembayaran === 'gagal';
    }

    public function isDonasiUmum(): bool
    {
        return is_null($this->kegiatan_id);
    }

    public function isDonasiKegiatan(): bool
    {
        return !is_null($this->kegiatan_id);
    }

    /**
     * Scopes
     */
    public function scopeBerhasil($query)
    {
        return $query->where('status_pembayaran', 'berhasil');
    }

    public function scopePending($query)
    {
        return $query->where('status_pembayaran', 'pending');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByKegiatan($query, $kegiatanId)
    {
        return $query->where('kegiatan_id', $kegiatanId);
    }

    public function scopeDonasiUmum($query)
    {
        return $query->whereNull('kegiatan_id');
    }

    public function scopeDonasiKegiatan($query)
    {
        return $query->whereNotNull('kegiatan_id');
    }

    public function scopeNotifikasiTerkirim($query)
    {
        return $query->where('notifikasi_terkirim', true);
    }

    public function scopeNotifikasiBelumTerkirim($query)
    {
        return $query->where('notifikasi_terkirim', false);
    }

    /**
     * Mutators
     */
    public function getFormattedJumlahAttribute(): string
    {
        return 'Rp ' . number_format($this->jumlah, 0, ',', '.');
    }
}