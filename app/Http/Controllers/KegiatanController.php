<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use Illuminate\Http\Request;

class KegiatanController extends Controller
{
    /**
     * F4, F22, F40, F41: Menampilkan daftar kegiatan
     * Bisa diakses umum (tanpa login)
     */
    public function index(Request $request)
    {
        $query = Kegiatan::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter kegiatan aktif dan tersedia (ada kuota)
        if ($request->has('tersedia') && $request->tersedia == true) {
            $query->tersedia();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'tanggal_mulai');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $kegiatan = $query->with(['relawan' => function($q) {
            $q->where('status', 'diterima');
        }])->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $kegiatan,
        ]);
    }

    /**
     * F41, F54: Menampilkan detail kegiatan
     * Bisa diakses umum
     */
    public function show($id)
    {
        $kegiatan = Kegiatan::with(['relawan.user', 'galeri'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $kegiatan,
        ]);
    }

    /**
     * F19: Menambah data kegiatan
     * Admin only
     */
    public function store(Request $request)
    {
        $this->authorize('create', Kegiatan::class);

        $validated = $request->validate([
            'judul' => 'required|string|max:200',
            'deskripsi' => 'required|string',
            'lokasi' => 'required|string',
            'kategori' => 'required|in:bantuan,pendidikan,kesehatan,sosial,lainnya',
            'gambar_url' => 'nullable|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'sometimes|in:aktif,selesai,dibatalkan',
            'butuh_relawan' => 'sometimes|boolean',
            'butuh_donasi' => 'sometimes|boolean',
            'kuota_relawan' => 'required|integer|min:0',
            'jenis_relawan' => 'nullable|array',
            'jenis_relawan.*' => 'string|max:100',
            'bidang_minat' => 'nullable|array',
            'bidang_minat.*' => 'string|max:100',
        ]);

        $kegiatan = Kegiatan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kegiatan berhasil ditambahkan',
            'data' => $kegiatan,
        ], 201);
    }

    /**
     * F20: Mengubah data kegiatan
     * Admin only
     */
    public function update(Request $request, $id)
    {
        $kegiatan = Kegiatan::findOrFail($id);
        $this->authorize('update', $kegiatan);

        $validated = $request->validate([
            'judul' => 'sometimes|string|max:200',
            'deskripsi' => 'sometimes|string',
            'lokasi' => 'sometimes|string',
            'kategori' => 'sometimes|in:bantuan,pendidikan,kesehatan,sosial,lainnya',
            'gambar_url' => 'nullable|string|max:255',
            'tanggal_mulai' => 'sometimes|date',
            'tanggal_selesai' => 'sometimes|date|after_or_equal:tanggal_mulai',
            'status' => 'sometimes|in:aktif,selesai,dibatalkan',
            'butuh_relawan' => 'sometimes|boolean',
            'butuh_donasi' => 'sometimes|boolean',
            'kuota_relawan' => 'sometimes|integer|min:0',
            'jenis_relawan' => 'nullable|array',
            'jenis_relawan.*' => 'string|max:100',
            'bidang_minat' => 'nullable|array',
            'bidang_minat.*' => 'string|max:100',
        ]);

        $kegiatan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kegiatan berhasil diperbarui',
            'data' => $kegiatan->fresh(),
        ]);
    }

    /**
     * F21: Menghapus data kegiatan
     * Admin only
     */
    public function destroy($id)
    {
        $kegiatan = Kegiatan::findOrFail($id);
        $this->authorize('delete', $kegiatan);

        $kegiatan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kegiatan berhasil dihapus',
        ]);
    }

    /**
     * F23: Mencari data kegiatan
     * Admin only
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Kegiatan::class);

        $query = $request->get('q', '');
        
        $kegiatan = Kegiatan::where('judul', 'like', "%{$query}%")
            ->orWhere('lokasi', 'like', "%{$query}%")
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $kegiatan,
        ]);
    }

    /**
     * F53: Menampilkan kegiatan yang membuka pendaftaran relawan
     */
    public function availableForVolunteer()
    {
        $kegiatan = Kegiatan::aktif()
            ->tersedia()
            ->orderBy('tanggal_mulai', 'asc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $kegiatan,
        ]);
    }

    /**
     * F80: Mengubah status kegiatan (draft, aktif, selesai)
     * Admin only
     */
    public function updateStatus(Request $request, $id)
    {
        $kegiatan = Kegiatan::findOrFail($id);
        $this->authorize('update', $kegiatan);

        $request->validate([
            'status' => 'required|in:aktif,selesai,dibatalkan',
        ]);

        $kegiatan->update(['status' => $request->status]);

        // TODO: F87 - Kirim notifikasi jika kegiatan dibatalkan
        if ($request->status === 'dibatalkan') {
            // Trigger notification to all registered volunteers
        }

        return response()->json([
            'success' => true,
            'message' => 'Status kegiatan berhasil diperbarui',
            'data' => $kegiatan->fresh(),
        ]);
    }
}