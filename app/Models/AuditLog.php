<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['user_id', 'user_nama', 'aksi', 'target'];

    /**
     * Catat satu aktivitas. Dibungkus try/catch agar tidak pernah
     * menggagalkan aksi utama bila pencatatan bermasalah.
     */
    public static function record($user, string $aksi, ?string $target = null): void
    {
        try {
            static::create([
                'user_id' => $user?->id,
                'user_nama' => $user?->nama_lengkap ?? 'Sistem',
                'aksi' => $aksi,
                'target' => $target,
            ]);
        } catch (\Throwable $e) {
            // abaikan — audit tidak boleh memblok operasi
        }
    }
}
