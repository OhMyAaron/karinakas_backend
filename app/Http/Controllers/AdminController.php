<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Kegiatan;
use App\Models\Kampanye;
use App\Models\Donasi;
use App\Models\Relawan;
use App\Models\Galeri;
use App\Models\AuditLog;
use App\Models\NotifikasiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Dashboard Statistik
     * Admin: tanpa nominal donasi
     * Super Admin: dengan total nominal donasi
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAdminDashboard', User::class);

        $stats = [
            'total_pengguna' => User::count(),
            'total_kegiatan' => Kegiatan::count(),
            'kegiatan_aktif' => Kegiatan::where('status', 'aktif')->count(),
            'kegiatan_selesai' => Kegiatan::where('status', 'selesai')->count(),
            'total_kampanye' => Kampanye::count(),
            'kampanye_published' => Kampanye::where('status', 'published')->count(),
            'total_galeri' => Galeri::count(),
            'total_relawan' => Relawan::count(),
            'relawan_pending' => Relawan::where('status', 'pending')->count(),
            'relawan_diterima' => Relawan::where('status', 'diterima')->count(),
            'relawan_lansia' => Relawan::where('jenis', 'Lansia')->count(),
            'relawan_kebencanaan' => Relawan::where('jenis', 'Kebencanaan')->count(),
            'total_donasi' => Donasi::count(),
            'jumlah_transaksi_donasi' => Donasi::where('status_pembayaran', 'berhasil')->count(),
        ];

        // Super Admin bisa lihat nominal
        if ($user->isSuperAdmin()) {
            $stats['total_nominal_donasi'] = Donasi::where('status_pembayaran', 'berhasil')->sum('jumlah');
        }

        // Grafik donasi per bulan (admin: jumlah transaksi, super admin: nominal)
        $donasiPerBulan = Donasi::where('status_pembayaran', 'berhasil')
            ->select(
                DB::raw('DATE_FORMAT(tanggal_donasi, "%Y-%m") as bulan'),
                DB::raw('COUNT(*) as jumlah_transaksi')
            );

        if ($user->isSuperAdmin()) {
            $donasiPerBulan->addSelect(DB::raw('SUM(jumlah) as total_nominal'));
        }

        $donasiPerBulan = $donasiPerBulan->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'grafik_donasi_per_bulan' => $donasiPerBulan,
        ]);
    }

    /**
     * F59: Rekapan data relawan
     * Admin & Super Admin
     */
    public function rekapanRelawan(Request $request)
    {
        $this->authorize('viewAdminDashboard', User::class);

        $query = Relawan::with(['user', 'kegiatan']);

        // Filter by kegiatan
        if ($request->has('kegiatan_id')) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('tanggal_daftar', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('tanggal_daftar', '<=', $request->date_to);
        }

        $relawan = $query->orderBy('tanggal_daftar', 'desc')->get();

        // Format data
        $data = $relawan->map(function ($r) {
            return [
                'id' => $r->id,
                'nama_relawan' => $r->user->nama_lengkap,
                'email' => $r->user->email,
                'no_telepon' => $r->user->no_telepon,
                'kegiatan' => $r->kegiatan ? $r->kegiatan->judul : 'Relawan Umum',
                'tanggal_daftar' => $r->tanggal_daftar->format('d F Y'),
                'status' => $r->status,
                'nomor_ktp' => $r->nomor_ktp,
                'kebutuhan_khusus' => $r->kebutuhan_khusus,
                'motivasi' => $r->motivasi,
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * F61: Rekapan kegiatan
     * Admin & Super Admin
     */
    public function rekapanKegiatan(Request $request)
    {
        $this->authorize('viewAdminDashboard', User::class);

        $query = Kegiatan::withCount([
            'relawan as jumlah_relawan_diterima' => function($q) {
                $q->where('status', 'diterima');
            },
            'donasi as jumlah_donasi' => function($q) {
                $q->where('status_pembayaran', 'berhasil');
            }
        ]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('tanggal_mulai', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('tanggal_selesai', '<=', $request->date_to);
        }

        $kegiatan = $query->orderBy('tanggal_mulai', 'desc')->get();

        return response()->json([
            'success' => true,
            'total' => $kegiatan->count(),
            'data' => $kegiatan,
        ]);
    }

    /**
     * F60: Rekapan donasi
     * Admin: tanpa nominal
     * Super Admin: dengan nominal
     */
    public function rekapanDonasi(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAdminDashboard', User::class);

        $query = Donasi::with(['user', 'kegiatan'])
            ->where('status_pembayaran', 'berhasil');

        // Filters
        if ($request->has('kegiatan_id')) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('tanggal_donasi', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('tanggal_donasi', '<=', $request->date_to);
        }

        $donasi = $query->orderBy('tanggal_donasi', 'desc')->get();

        // Format data
        $data = $donasi->map(function ($d) use ($user) {
            $item = [
                'id' => $d->id,
                'nama_donatur' => $d->user->nama_lengkap,
                'email' => $d->user->email,
                'tanggal' => $d->tanggal_donasi->format('d F Y'),
                'kegiatan' => $d->kegiatan ? $d->kegiatan->judul : 'Donasi Umum',
                'metode_pembayaran' => $d->metode_pembayaran,
                'tujuan_donasi' => $d->tujuan_donasi,
                'status' => $d->status_pembayaran,
            ];

            // Super Admin bisa lihat nominal
            if ($user->isSuperAdmin()) {
                $item['nominal'] = $d->jumlah;
                $item['nominal_formatted'] = $d->formatted_jumlah;
            }

            return $item;
        });

        $result = [
            'success' => true,
            'total_transaksi' => $data->count(),
            'data' => $data,
        ];

        // Super Admin bisa lihat total nominal
        if ($user->isSuperAdmin()) {
            $result['total_nominal'] = $donasi->sum('jumlah');
        }

        return response()->json($result);
    }

    /**
     * F65: Export rekapan (PDF/Excel)
     * Admin & Super Admin
     */
    public function exportRekapan(Request $request)
    {
        $this->authorize('viewAdminDashboard', User::class);

        $request->validate([
            'tipe' => 'required|in:relawan,kegiatan,donasi',
            'format' => 'required|in:excel,pdf',
        ]);

        // TODO: Implement export logic with Laravel Excel or DomPDF
        // Generate file based on tipe and format

        return response()->json([
            'success' => true,
            'message' => 'Export sedang diproses',
            'download_url' => null, // Will provide download URL
        ]);
    }

    /**
     * F36, F37, F38: Mengelola data pengguna (Super Admin only).
     *
     * Pemeriksaan memakai isSuperAdmin(), bukan $this->authorize(). Gate::before pada
     * AppServiceProvider memberikan seluruh ability kepada admin maupun super_admin,
     * sehingga authorize() selalu lolos dan admin biasa ikut membaca daftar akun.
     */
    public function users(Request $request)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat melihat daftar akun pengguna.');
        }

        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Daftar Audit Log (Super Admin only).
     */
    public function auditLogs(Request $request)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat melihat audit log.');
        }

        $query = AuditLog::query();
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('user_nama', 'like', "%{$q}%")
                    ->orWhere('aksi', 'like', "%{$q}%")
                    ->orWhere('target', 'like', "%{$q}%");
            });
        }

        $perPage = (int) $request->get('per_page', 100);
        return response()->json([
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->paginate($perPage),
        ]);
    }

    /**
     * Daftar riwayat notifikasi terkirim (Admin & Super Admin).
     */
    public function notifikasiLogs(Request $request)
    {
        $this->authorize('viewAdminDashboard', User::class);

        $query = NotifikasiLog::query();
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        $perPage = (int) $request->get('per_page', 100);
        return response()->json([
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->paginate($perPage),
        ]);
    }

    /**
     * Membuat akun pengguna baru (Super Admin only).
     */
    public function storeUser(Request $request)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat membuat akun pengguna.');
        }

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,admin,super_admin',
        ]);

        $user = User::create($validated);

        AuditLog::record($request->user(), 'Menambah pengguna', $user->nama_lengkap . ' (' . $user->role . ')');

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dibuat',
            'data' => $user,
        ], 201);
    }

    /**
     * Memperbarui pengguna — termasuk ubah role (Super Admin only).
     */
    public function updateUser(Request $request, $id)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat mengubah data pengguna.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama_lengkap' => 'sometimes|string|max:150',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'username' => "sometimes|string|max:50|unique:users,username,{$id}",
            'role' => 'sometimes|in:user,admin,super_admin',
            'password' => 'sometimes|string|min:8',
        ]);

        // Cegah Super Admin menurunkan/mengubah role dirinya sendiri (anti lock-out)
        if (array_key_exists('role', $validated)
            && $user->id === $request->user()->id
            && $validated['role'] !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat mengubah role akun Anda sendiri.',
            ], 422);
        }

        $user->update($validated);

        $aksi = array_key_exists('role', $validated) ? 'Mengubah role pengguna' : 'Mengubah data pengguna';
        $target = $user->nama_lengkap . (array_key_exists('role', $validated) ? ' → ' . $validated['role'] : '');
        AuditLog::record($request->user(), $aksi, $target);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil diperbarui',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Reset kata sandi pengguna ke sandi sementara (Super Admin only).
     */
    public function resetUserPassword(Request $request, $id)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat mereset kata sandi.');
        }

        $user = User::findOrFail($id);

        $newPassword = $request->input('password', 'karinakas123');
        $request->merge(['password' => $newPassword]);
        $request->validate(['password' => 'string|min:8']);

        $user->update(['password' => $newPassword]); // auto-hash via cast

        AuditLog::record($request->user(), 'Reset password pengguna', $user->nama_lengkap . ' (' . $user->email . ')');

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil direset',
            'password_sementara' => $newPassword,
        ]);
    }

    /**
     * F36: Menghapus data pengguna (Super Admin only).
     *
     * Penjagaan ini penting karena donasi.user_id memakai ON DELETE CASCADE, sehingga
     * menghapus akun ikut melenyapkan seluruh catatan donasi miliknya. Admin biasa yang
     * memang dilarang melihat data keuangan karenanya tidak boleh menghapus akun.
     */
    public function deleteUser(Request $request, $id)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat menghapus akun pengguna.');
        }

        $user = User::findOrFail($id);

        // Tidak bisa hapus super_admin
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin tidak dapat dihapus',
            ], 403);
        }

        $nama = $user->nama_lengkap;
        $user->delete();

        AuditLog::record($request->user(), 'Menghapus pengguna', $nama);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus',
        ]);
    }
}