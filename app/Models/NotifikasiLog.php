<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotifikasiLog extends Model
{
    use HasUuids;

    protected $table = 'notifikasi_logs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'tipe', 'penerima', 'judul', 'isi', 'trigger', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Catat satu notifikasi terkirim. Dibungkus try/catch agar aman.
     */
    public static function record(array $data): void
    {
        try {
            static::create(array_merge(['status' => 'terkirim'], $data));
        } catch (\Throwable $e) {
            // abaikan
        }
    }
}
