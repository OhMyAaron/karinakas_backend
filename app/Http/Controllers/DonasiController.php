<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Models\Kegiatan;
use App\Models\AuditLog;
use App\Models\NotifikasiLog;
use Illuminate\Http\Request;

class DonasiController extends Controller
{
    /**
     * Medan keuangan yang hanya boleh sampai ke Super Admin (FR SIKAS 512).
     * Bukti transfer termasuk, karena nominal terbaca pada gambarnya.
     */
    private const MEDAN_KEUANGAN = [
        'jumlah', 'biaya_transfer', 'kode_transaksi', 'rekening_pengirim', 'bukti_transfer',
    ];

    /** Sembunyikan medan keuangan dari admin biasa pada paginator donasi. */
    private function sembunyikanKeuangan(Request $request, $paginator): void
    {
        if ($request->user()->isSuperAdmin()) {
            return;
        }
        $paginator->getCollection()->makeHidden(self::MEDAN_KEUANGAN);
    }

    /**
     * Verifikasi, pengubahan, dan penghapusan donasi khusus Super Admin
     * (FR SIKAS 509–511, 513). Gate::before meloloskan authorize() untuk semua
     * admin, jadi pembatasan peran harus dicek manual di sini.
     */
    private function wajibSuperAdmin(Request $request, string $tindakan): void
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, "Hanya Super Admin yang dapat {$tindakan}.");
        }
    }

    /**
     * F24, F28: Menambah & Menampilkan data donasi
     * User terdaftar untuk create, Admin untuk view all
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'kegiatan_id' => 'required|uuid|exists:kegiatan,id',   // wajib: donasi harus terikat kegiatan
            'jumlah' => 'required|numeric|min:1',          // tanpa batas maksimal (sinkron frontend)
            'metode_pembayaran' => 'required|string|max:50',
            'tujuan_donasi' => 'required|string',
            'keterangan' => 'nullable|string',
            'anonim' => 'sometimes|boolean',
        ], [
            'kegiatan_id.required' => 'Silakan pilih kegiatan yang ingin Anda donasikan.',
        ]);

        // Kegiatan wajib: harus aktif & memang membuka donasi
        $kegiatan = Kegiatan::findOrFail($validated['kegiatan_id']);

        if (!$kegiatan->isAktif()) {
            return response()->json([
                'success' => false,
                'message' => 'Kegiatan ini sudah tidak menerima donasi',
            ], 400);
        }

        if (!$kegiatan->butuh_donasi) {
            return response()->json([
                'success' => false,
                'message' => 'Kegiatan ini tidak membuka donasi',
            ], 400);
        }

        $donasi = Donasi::create([
            'user_id' => $user->id,
            'kegiatan_id' => $validated['kegiatan_id'] ?? null,
            'jumlah' => $validated['jumlah'],
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'tujuan_donasi' => $validated['tujuan_donasi'],
            'keterangan' => $validated['keterangan'] ?? null,
            'anonim' => $validated['anonim'] ?? false,
            'status_pembayaran' => 'pending',
            'notifikasi_terkirim' => false,
        ]);

        // TODO: Integrate with payment gateway
        // Generate payment URL

        return response()->json([
            'success' => true,
            'message' => 'Transaksi donasi berhasil dibuat',
            'data' => $donasi->load(['kegiatan']),
            'payment_url' => null, // Will be provided by payment gateway
        ], 201);
    }

    /**
     * Upload bukti transfer (pemilik donasi).
     */
    public function uploadBukti(Request $request, $id)
    {
        $donasi = Donasi::findOrFail($id);

        // Hanya pemilik donasi yang boleh upload bukti
        if ($donasi->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengunggah bukti untuk donasi ini.',
            ], 403);
        }

        $request->validate([
            'bukti' => 'required|image|max:4096', // gambar, maks 4MB
        ]);

        // Hapus bukti lama jika ada
        if ($donasi->bukti_transfer) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($donasi->bukti_transfer);
        }

        $path = $request->file('bukti')->store('bukti_transfer', 'public');
        $donasi->update(['bukti_transfer' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Bukti transfer berhasil diunggah. Menunggu verifikasi admin.',
            'url' => asset('storage/' . $path),
            'data' => $donasi->fresh(['kegiatan']),
        ]);
    }

    /**
     * F79, F82, F86: Mengubah status donasi & trigger notifikasi
     * Called by payment gateway webhook
     */
    public function updateStatus(Request $request, $id)
    {
        $this->wajibSuperAdmin($request, 'memverifikasi pembayaran donasi');

        $donasi = Donasi::findOrFail($id);

        $request->validate([
            'status_pembayaran' => 'required|in:pending,berhasil,gagal',
        ]);

        $oldStatus = $donasi->status_pembayaran;
        $newStatus = $request->status_pembayaran;

        $donasi->update(['status_pembayaran' => $newStatus]);

        $fresh = $donasi->fresh(['user', 'kegiatan']);

        // Catat audit perubahan status
        AuditLog::record(
            $request->user(),
            'Mengubah status donasi',
            ($fresh->user?->nama_lengkap ?? 'Donatur') . " → " . $newStatus
        );

        // Trigger notifikasi jika status berubah menjadi 'berhasil'
        if ($newStatus === 'berhasil' && $oldStatus !== 'berhasil') {
            // Kirim surel donasi terverifikasi; hasilnya null bila surel sudah pernah terkirim
            $hasil = app(NotifikasiController::class)->kirimNotifikasiDonasi($donasi->id);

            // Catat riwayat notifikasi ke database (untuk panel admin & profil user)
            // dengan status sesuai hasil pengiriman yang sebenarnya
            if ($hasil !== null) {
                $terkirim = (bool) ($hasil->getData(true)['success'] ?? false);
                $u = $fresh->user;
                NotifikasiLog::record([
                    'user_id' => $u?->id,
                    'tipe' => 'email',
                    'penerima' => $u?->email ?? '-',
                    'judul' => 'Donasi Anda telah diverifikasi',
                    'trigger' => 'donasi_verified',
                    'status' => $terkirim ? 'terkirim' : 'gagal',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status donasi berhasil diperbarui',
            'data' => $fresh,
        ]);
    }

    /**
     * F12: Menampilkan history donasi pengguna
     * User terdaftar
     */
    public function myDonations(Request $request)
    {
        $user = $request->user();

        $donasi = Donasi::where('user_id', $user->id)
            ->with(['kegiatan'])
            ->orderBy('tanggal_donasi', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $donasi,
        ]);
    }

    /**
     * Get all donations (Admin only)
     * F28: Menampilkan data donasi
     * Admin: tanpa nominal
     * Super Admin: dengan nominal
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Donasi::class);

        $query = Donasi::with(['user', 'kegiatan']);

        // Filter
        if ($request->has('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        if ($request->has('kegiatan_id')) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('tanggal_donasi', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('tanggal_donasi', '<=', $request->date_to);
        }

        // Hormati per_page dari klien (panel admin memakai 100 lalu mengikuti halaman
        // berikutnya). Dibatasi 1–100 agar satu permintaan tidak memuat seluruh tabel.
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        $donasi = $query->orderBy('tanggal_donasi', 'desc')->paginate($perPage);

        // Admin biasa tidak menerima data keuangan sama sekali (FR SIKAS 512)
        $this->sembunyikanKeuangan($request, $donasi);

        return response()->json([
            'success' => true,
            'data' => $donasi,
        ]);
    }

    /**
     * F60: Menampilkan laporan data donasi
     * Admin: jumlah transaksi saja
     * Super Admin: total nominal + jumlah transaksi
     */
    public function report(Request $request)
    {
        $this->authorize('viewAny', Donasi::class);

        $query = Donasi::berhasil();

        // Filters
        if ($request->has('kegiatan_id')) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('tanggal_donasi', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('tanggal_donasi', '<=', $request->date_to);
        }

        $jumlahTransaksi = $query->count();
        
        $data = [
            'jumlah_transaksi' => $jumlahTransaksi,
        ];

        // Super Admin bisa lihat nominal
        if ($request->user()->isSuperAdmin()) {
            $data['total_nominal'] = $query->sum('jumlah');
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * F25: Mengubah data donasi (Admin only)
     */
    public function update(Request $request, $id)
    {
        $this->authorize('update', Donasi::class);
        $this->wajibSuperAdmin($request, 'mengubah data donasi');

        $donasi = Donasi::findOrFail($id);

        $validated = $request->validate([
            'status_pembayaran' => 'sometimes|in:pending,berhasil,gagal',
            'keterangan' => 'sometimes|string',
            'jumlah' => 'sometimes|numeric|min:1',
            'metode_pembayaran' => 'sometimes|string|max:50',
            'tujuan_donasi' => 'sometimes|string',
            // Detail pembayaran (idealnya hanya Super Admin)
            'biaya_transfer' => 'sometimes|numeric|min:0',
            'kode_transaksi' => 'nullable|string|max:100',
            'rekening_pengirim' => 'nullable|string|max:150',
        ]);

        $donasi->update($validated);

        $fresh = $donasi->fresh(['user', 'kegiatan']);
        AuditLog::record($request->user(), 'Mengubah data donasi', $fresh->user?->nama_lengkap ?? 'Donasi');

        return response()->json([
            'success' => true,
            'message' => 'Data donasi berhasil diperbarui',
            'data' => $fresh,
        ]);
    }

    /**
     * F26: Menghapus data donasi (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('delete', Donasi::class);
        $this->wajibSuperAdmin($request, 'menghapus data donasi');

        $donasi = Donasi::with('user')->findOrFail($id);
        $namaDonatur = $donasi->user?->nama_lengkap ?? 'Donasi';
        $donasi->delete();

        AuditLog::record(auth()->user(), 'Menghapus donasi', $namaDonatur);

        return response()->json([
            'success' => true,
            'message' => 'Data donasi berhasil dihapus',
        ]);
    }

    /**
     * F27: Mencari data donasi (Admin only)
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Donasi::class);

        $query = $request->get('q', '');

        $donasi = Donasi::with(['user', 'kegiatan'])
            ->whereHas('user', function($q) use ($query) {
                $q->where('nama_lengkap', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orWhereHas('kegiatan', function($q) use ($query) {
                $q->where('judul', 'like', "%{$query}%");
            })
            ->paginate(10);

        // Dulu jalur ini tidak menyamarkan apa pun — nominal bocor ke admin biasa
        $this->sembunyikanKeuangan($request, $donasi);

        return response()->json([
            'success' => true,
            'data' => $donasi,
        ]);
    }
}