<?php

namespace App\Http\Controllers;

use App\Models\Relawan;
use App\Models\Kegiatan;
use App\Models\AuditLog;
use App\Models\NotifikasiLog;
use App\Mail\RelawanDiterima;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RelawanController extends Controller
{
    /**
     * F55, F56: Menyimpan & Menampilkan status pendaftaran relawan
     * User terdaftar
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'kegiatan_id' => 'nullable|uuid|exists:kegiatan,id', // nullable: pendaftaran relawan umum
            'jenis' => 'required|string|max:100',                // opsi ditentukan admin per kegiatan
            'minat' => 'nullable|string|max:100',
            'kemampuan_diri' => 'nullable|string',
            'motivasi' => 'nullable|string',
            'nomor_ktp' => 'nullable|string|max:20',
            'kebutuhan_khusus' => 'nullable|string',             // keterbatasan diri
        ]);

        // Jika terikat kegiatan: cek ketersediaan & duplikasi
        if (!empty($validated['kegiatan_id'])) {
            $kegiatan = Kegiatan::findOrFail($validated['kegiatan_id']);

            if (!$kegiatan->isAktif()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kegiatan ini sudah tidak aktif',
                ], 400);
            }

            // Jika admin mendefinisikan opsi, pastikan pilihan sesuai
            $opsiJenis = $kegiatan->jenis_relawan ?? [];
            if (!empty($opsiJenis) && !in_array($validated['jenis'], $opsiJenis, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis relawan tidak sesuai dengan pilihan untuk kegiatan ini',
                ], 422);
            }
            $opsiMinat = $kegiatan->bidang_minat ?? [];
            if (!empty($opsiMinat) && !empty($validated['minat']) && !in_array($validated['minat'], $opsiMinat, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bidang minat tidak sesuai dengan pilihan untuk kegiatan ini',
                ], 422);
            }

            if ($kegiatan->kuota_relawan > 0 && $kegiatan->isKuotaPenuh()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kuota relawan sudah penuh',
                ], 400);
            }

            $existing = Relawan::where('user_id', $user->id)
                ->where('kegiatan_id', $validated['kegiatan_id'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah terdaftar di kegiatan ini',
                ], 400);
            }
        }

        $relawan = Relawan::create([
            'user_id' => $user->id,
            'kegiatan_id' => $validated['kegiatan_id'] ?? null,
            'jenis' => $validated['jenis'],
            'minat' => $validated['minat'] ?? null,
            'kemampuan_diri' => $validated['kemampuan_diri'] ?? null,
            'motivasi' => $validated['motivasi'] ?? null,
            'nomor_ktp' => $validated['nomor_ktp'] ?? null,
            'kebutuhan_khusus' => $validated['kebutuhan_khusus'] ?? null,
            'status' => 'pending',
        ]);

        // TODO: F58, F85 - Kirim notifikasi pendaftaran berhasil
        // TODO: F88 - Kirim notifikasi ke admin

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran relawan berhasil',
            'data' => $relawan->load(['kegiatan', 'user']),
        ], 201);
    }

    /**
     * F13: Menampilkan history keikutsertaan dalam kegiatan
     * User terdaftar
     */
    public function myRegistrations(Request $request)
    {
        $user = $request->user();
        
        $relawan = Relawan::where('user_id', $user->id)
            ->with(['kegiatan'])
            ->orderBy('tanggal_daftar', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $relawan,
        ]);
    }

    /**
     * F56: Menampilkan status pendaftaran relawan
     */
    public function status(Request $request, $id)
    {
        $relawan = Relawan::with(['kegiatan', 'user'])->findOrFail($id);

        // User hanya bisa lihat pendaftaran sendiri, admin bisa lihat semua
        if (!$request->user()->hasAnyRole(['admin', 'super_admin']) && 
            $relawan->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'success' => true,
            'data' => $relawan,
        ]);
    }

    /**
     * F81, F83: Mengubah status pendaftaran relawan
     * Admin only
     */
    public function updateStatus(Request $request, $id)
    {
        $this->authorize('update', Relawan::class);

        $relawan = Relawan::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,diterima,ditolak',
        ]);

        $relawan->update(['status' => $request->status]);

        $fresh = $relawan->fresh(['kegiatan', 'user']);

        AuditLog::record(
            $request->user(),
            'Mengubah status relawan',
            ($fresh->user?->nama_lengkap ?? 'Relawan') . ' → ' . $request->status
        );

        // F85 - Kirim notifikasi saat relawan diterima
        if ($request->status === 'diterima') {
            $u = $fresh->user;

            // ── Email nyata (butuh SMTP di .env; jika MAIL_MAILER=log → tercatat di log) ──
            $emailStatus = 'terkirim';
            if ($u && $u->email) {
                try {
                    Mail::to($u->email)->send(new RelawanDiterima(
                        $u->nama_lengkap,
                        $fresh->jenis,
                        $fresh->kegiatan?->judul
                    ));
                } catch (\Throwable $e) {
                    $emailStatus = 'gagal';
                    Log::error('Gagal kirim email relawan diterima: ' . $e->getMessage());
                }
            }
            NotifikasiLog::record([
                'user_id' => $u?->id,
                'tipe' => 'email',
                'penerima' => $u?->email ?? '-',
                'judul' => 'Selamat datang sebagai relawan Karinakas',
                'trigger' => 'relawan_approved',
                'status' => $emailStatus,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status relawan berhasil diperbarui',
            'data' => $fresh,
        ]);
    }

    /**
     * F57: Membatalkan pendaftaran relawan
     * User terdaftar
     */
    public function cancel(Request $request, $id)
    {
        $relawan = Relawan::findOrFail($id);

        // Only owner can cancel
        if ($relawan->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        // Cannot cancel if already accepted
        if ($relawan->isDiterima()) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran yang sudah diterima tidak bisa dibatalkan',
            ], 400);
        }

        $relawan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil dibatalkan',
        ]);
    }

    /**
     * Get all relawan for a kegiatan
     * Admin only
     */
    public function byKegiatan(Request $request, $kegiatanId)
    {
        $this->authorize('viewAny', Relawan::class);

        $query = Relawan::where('kegiatan_id', $kegiatanId)
            ->with(['user']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $relawan = $query->orderBy('tanggal_daftar', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $relawan,
        ]);
    }

    /**
     * Get ALL relawan (Admin only) — daftar lengkap untuk panel admin.
     * Mendukung filter status, jenis, dan pencarian nama/email.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Relawan::class);

        $query = Relawan::with(['user', 'kegiatan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('kegiatan_id')) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('user', function ($sub) use ($q) {
                $sub->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $perPage = min(max((int) $request->get('per_page', 100), 1), 100);   // dibatasi 1–100
        $relawan = $query->orderBy('tanggal_daftar', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $relawan,
        ]);
    }

    /**
     * Hapus pendaftaran relawan (Admin only).
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('delete', Relawan::class);

        $relawan = Relawan::with('user')->findOrFail($id);
        $namaRelawan = $relawan->user?->nama_lengkap ?? 'Relawan';
        $relawan->delete();

        AuditLog::record($request->user(), 'Menghapus relawan', $namaRelawan);

        return response()->json([
            'success' => true,
            'message' => 'Data relawan berhasil dihapus',
        ]);
    }

    /**
     * Get pending relawan (for admin dashboard)
     * Admin only
     */
    public function pending(Request $request)
    {
        $this->authorize('viewAny', Relawan::class);

        $relawan = Relawan::pending()
            ->with(['user', 'kegiatan'])
            ->orderBy('tanggal_daftar', 'asc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $relawan,
        ]);
    }
}