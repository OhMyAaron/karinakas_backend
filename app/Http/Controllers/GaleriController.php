<?php

namespace App\Http\Controllers;

use App\Models\Galeri;
use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GaleriController extends Controller
{
    /**
     * F33, F44: Menampilkan galeri dokumentasi kegiatan
     * Public access
     */
    public function index(Request $request)
    {
        $query = Galeri::with('kegiatan');

        // Filter by kegiatan
        if ($request->has('kegiatan_id')) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }

        // Filter by tipe
        if ($request->has('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        $galeri = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $galeri,
        ]);
    }

    /**
     * Get galeri by kegiatan
     * Public access
     */
    public function byKegiatan($kegiatanId)
    {
        $kegiatan = Kegiatan::findOrFail($kegiatanId);
        
        $galeri = Galeri::where('kegiatan_id', $kegiatanId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'kegiatan' => $kegiatan,
            'data' => $galeri,
        ]);
    }

    /**
     * F29: Menambah data galeri
     * Admin only
     */
    public function store(Request $request)
    {
        $this->authorize('create', Galeri::class);

        $hasFile = $request->hasFile('gambar');

        $validated = $request->validate([
            'kegiatan_id' => 'nullable|uuid|exists:kegiatan,id', // nullable: galeri berdiri sendiri
            'judul' => 'required|string|max:200',
            'nama_foto' => 'nullable|string|max:200', // nama tiap foto
            'deskripsi' => 'nullable|string',
            'gambar' => $hasFile ? 'image|max:5120' : 'nullable', // upload file (maks 5MB)
            'gambar_url' => $hasFile ? 'nullable|string|max:255' : 'required|string|max:255',
            'kategori' => 'sometimes|in:sosial,pendidikan,kesehatan,bantuan,relawan,lainnya',
            'tanggal' => 'nullable|date',
            'tipe' => 'sometimes|in:foto,video',
        ]);

        if ($hasFile) {
            // Default nama foto dari nama file asli bila tak diisi
            if (empty($validated['nama_foto'])) {
                $validated['nama_foto'] = pathinfo($request->file('gambar')->getClientOriginalName(), PATHINFO_FILENAME);
            }
            $validated['gambar_url'] = $request->file('gambar')->store('galeri', 'public');
        }
        unset($validated['gambar']);

        $galeri = Galeri::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Galeri berhasil ditambahkan',
            'data' => $galeri->load('kegiatan'),
        ], 201);
    }

    /**
     * Upload banyak foto sekaligus untuk satu judul kegiatan
     * Admin only
     */
    public function bulkUpload(Request $request)
    {
        $this->authorize('create', Galeri::class);

        $validated = $request->validate([
            'kegiatan_id' => 'nullable|uuid|exists:kegiatan,id',
            'judul' => 'required|string|max:200',
            'deskripsi' => 'nullable|string',
            'kategori' => 'sometimes|in:sosial,pendidikan,kesehatan,bantuan,relawan,lainnya',
            'tanggal' => 'nullable|date',
            'gambar' => 'required|array|min:1',
            'gambar.*' => 'image|max:5120', // tiap file maks 5MB
            'nama_foto' => 'nullable|array',        // nama tiap foto (sejajar urutan file)
            'nama_foto.*' => 'nullable|string|max:200',
        ]);

        // Nama per foto (dari input admin); jika kosong → pakai nama file asli
        $names = $request->input('nama_foto', []);

        $created = [];
        foreach ($request->file('gambar') as $i => $file) {
            $nama = $names[$i] ?? null;
            if (empty(trim((string) $nama))) {
                $nama = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
            $created[] = Galeri::create([
                'kegiatan_id' => $validated['kegiatan_id'] ?? null,
                'judul' => $validated['judul'],
                'nama_foto' => $nama,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'gambar_url' => $file->store('galeri', 'public'),
                'kategori' => $validated['kategori'] ?? 'sosial',
                'tanggal' => $validated['tanggal'] ?? null,
                'tipe' => 'foto',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' foto berhasil diupload',
            'data' => $created,
        ], 201);
    }

    /**
     * F30: Mengubah data galeri
     * Admin only
     */
    public function update(Request $request, $id)
    {
        $galeri = Galeri::findOrFail($id);
        $this->authorize('update', $galeri);

        $hasFile = $request->hasFile('gambar');

        $validated = $request->validate([
            'kegiatan_id' => 'nullable|uuid|exists:kegiatan,id',
            'judul' => 'sometimes|string|max:200',
            'nama_foto' => 'sometimes|nullable|string|max:200',
            'deskripsi' => 'nullable|string',
            'gambar' => $hasFile ? 'image|max:5120' : 'nullable',
            'gambar_url' => 'sometimes|nullable|string|max:255',
            'kategori' => 'sometimes|in:sosial,pendidikan,kesehatan,bantuan,relawan,lainnya',
            'tanggal' => 'nullable|date',
            'tipe' => 'sometimes|in:foto,video',
        ]);

        if ($hasFile) {
            // Hapus file lama jika tersimpan lokal (bukan URL eksternal)
            if ($galeri->gambar_url && !str_starts_with($galeri->gambar_url, 'http')) {
                Storage::disk('public')->delete($galeri->gambar_url);
            }
            $validated['gambar_url'] = $request->file('gambar')->store('galeri', 'public');
        }
        unset($validated['gambar']);

        $galeri->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Galeri berhasil diperbarui',
            'data' => $galeri->fresh('kegiatan'),
        ]);
    }

    /**
     * F31: Menghapus data galeri
     * Admin only
     */
    public function destroy($id)
    {
        $galeri = Galeri::findOrFail($id);
        $this->authorize('delete', $galeri);

        // Hapus file fisik jika tersimpan lokal (bukan URL eksternal)
        if ($galeri->gambar_url && !str_starts_with($galeri->gambar_url, 'http')) {
            Storage::disk('public')->delete($galeri->gambar_url);
        }

        $galeri->delete();

        return response()->json([
            'success' => true,
            'message' => 'Galeri berhasil dihapus',
        ]);
    }

    /**
     * F32: Mencari data galeri
     * Admin only
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Galeri::class);

        $query = $request->get('q', '');

        $galeri = Galeri::with('kegiatan')
            ->where('judul', 'like', "%{$query}%")
            ->orWhere('deskripsi', 'like', "%{$query}%")
            ->orWhereHas('kegiatan', function($q) use ($query) {
                $q->where('judul', 'like', "%{$query}%");
            })
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $galeri,
        ]);
    }

    /**
     * Get latest galeri for homepage
     * Public access
     */
    public function latest()
    {
        $galeri = Galeri::with('kegiatan')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $galeri,
        ]);
    }
}