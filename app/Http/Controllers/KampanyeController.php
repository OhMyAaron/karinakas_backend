<?php

namespace App\Http\Controllers;

use App\Models\Kampanye;
use Illuminate\Http\Request;

class KampanyeController extends Controller
{
    /**
     * F3, F17, F42, F43: Menampilkan daftar kampanye dan detail
     * Public access
     */
    public function index(Request $request)
    {
        $query = Kampanye::query();

        // Untuk public, hanya tampilkan yang published
        if (!$request->user() || !$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            $query->published();
        }

        // Filter by kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('konten', 'like', "%{$search}%");
            });
        }

        $kampanye = $query->orderBy('tanggal_publish', 'desc')->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $kampanye,
        ]);
    }

    /**
     * F43: Menampilkan detail kampanye
     * Public access
     */
    public function show($id)
    {
        $kampanye = Kampanye::findOrFail($id);

        // Public hanya bisa lihat yang published
        if ($kampanye->status !== 'published' && 
            (!auth()->check() || !auth()->user()->hasAnyRole(['admin', 'super_admin']))) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => $kampanye,
        ]);
    }

    /**
     * F14: Menambah data kampanye
     * Admin only
     */
    public function store(Request $request)
    {
        $this->authorize('create', Kampanye::class);

        $validated = $request->validate([
            'judul' => 'required|string|max:200',
            'konten' => 'required|string',
            'ringkasan' => 'nullable|string',
            'gambar_url' => 'nullable|string|max:255',
            'penulis' => 'nullable|string|max:100',
            'penulis_avatar' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'waktu_baca' => 'nullable|string|max:20',
            'kategori' => 'required|in:sosial,pendidikan,kesehatan,bantuan,kegiatan,pengumuman,lainnya',
            'status' => 'sometimes|in:draft,published,archived',
            'tanggal_publish' => 'nullable|date',
        ]);

        // Auto-set tanggal_publish jika status = published
        if (($validated['status'] ?? null) === 'published' && empty($validated['tanggal_publish'])) {
            $validated['tanggal_publish'] = now();
        }

        $kampanye = Kampanye::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kampanye berhasil ditambahkan',
            'data' => $kampanye,
        ], 201);
    }

    /**
     * F15: Mengubah data kampanye
     * Admin only
     */
    public function update(Request $request, $id)
    {
        $kampanye = Kampanye::findOrFail($id);
        $this->authorize('update', $kampanye);

        $validated = $request->validate([
            'judul' => 'sometimes|string|max:200',
            'konten' => 'sometimes|string',
            'ringkasan' => 'nullable|string',
            'gambar_url' => 'nullable|string|max:255',
            'penulis' => 'nullable|string|max:100',
            'penulis_avatar' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'waktu_baca' => 'nullable|string|max:20',
            'kategori' => 'sometimes|in:sosial,pendidikan,kesehatan,bantuan,kegiatan,pengumuman,lainnya',
            'status' => 'sometimes|in:draft,published,archived',
            'tanggal_publish' => 'nullable|date',
        ]);

        // Auto-set tanggal_publish jika status berubah menjadi published
        if (isset($validated['status']) && $validated['status'] === 'published' && !$kampanye->tanggal_publish) {
            $validated['tanggal_publish'] = now();
        }

        $kampanye->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kampanye berhasil diperbarui',
            'data' => $kampanye->fresh(),
        ]);
    }

    /**
     * F16: Menghapus data kampanye
     * Admin only
     */
    public function destroy($id)
    {
        $kampanye = Kampanye::findOrFail($id);
        $this->authorize('delete', $kampanye);

        $kampanye->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kampanye berhasil dihapus',
        ]);
    }

    /**
     * F18: Mencari data kampanye
     * Admin only
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Kampanye::class);

        $query = $request->get('q', '');

        $kampanye = Kampanye::where('judul', 'like', "%{$query}%")
            ->orWhere('konten', 'like', "%{$query}%")
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $kampanye,
        ]);
    }

    /**
     * Get kampanye by kategori
     * Public access
     */
    public function byKategori($kategori)
    {
        $kampanye = Kampanye::published()
            ->where('kategori', $kategori)
            ->orderBy('tanggal_publish', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $kampanye,
        ]);
    }

    /**
     * Get recent kampanye
     * Public access
     */
    public function recent()
    {
        $kampanye = Kampanye::published()
            ->orderBy('tanggal_publish', 'desc')
            ->take(6)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $kampanye,
        ]);
    }
}