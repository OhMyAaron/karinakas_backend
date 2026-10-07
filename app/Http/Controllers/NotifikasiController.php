<?php

namespace App\Http\Controllers;

use App\Mail\DonasiDiverifikasi;
use App\Models\Donasi;
use App\Models\NotifikasiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifikasiController extends Controller
{
    /**
     * Riwayat notifikasi milik user yang sedang login (untuk halaman Profil).
     */
    public function myNotifications(Request $request)
    {
        $notif = NotifikasiLog::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notif,
        ]);
    }

    /**
     * Kirim ucapan terima kasih setelah pembayaran donasi diverifikasi.
     * Dipanggil otomatis dari DonasiController. Surel adalah satu-satunya
     * kanal pemberitahuan sistem; pengiriman WhatsApp ditiadakan.
     */
    public function kirimNotifikasiDonasi($donasiId)
    {
        $donasi = Donasi::with(['user', 'kegiatan'])->findOrFail($donasiId);

        // Skip jika notifikasi sudah terkirim
        if ($donasi->notifikasi_terkirim) {
            return;
        }

        // Surel hanya untuk donasi yang sudah diverifikasi
        if ($donasi->status_pembayaran !== 'berhasil') {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi hanya dikirim untuk donasi berstatus berhasil',
            ], 422);
        }

        $success = false;

        try {
            $this->kirimEmail($donasi);

            // Update flag notifikasi_terkirim
            $donasi->update(['notifikasi_terkirim' => true]);

            $success = true;

            // Log notifikasi
            $this->logNotifikasi([
                'donasi_id' => $donasi->id,
                'channel' => 'email',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi donasi: ' . $e->getMessage());
            
            $this->logNotifikasi([
                'donasi_id' => $donasi->id,
                'channel' => 'email',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notifikasi berhasil dikirim' : 'Gagal mengirim notifikasi',
        ]);
    }

    /**
     * Kirim Email ucapan terima kasih
     */
    private function kirimEmail($donasi)
    {
        $user = $donasi->user;

        // Tanpa alamat surel tujuan, pengiriman dianggap gagal (ditangkap pemanggil)
        if (!$user || !$user->email) {
            throw new \RuntimeException('Donatur tidak memiliki alamat surel');
        }

        Mail::to($user->email)->send(new DonasiDiverifikasi(
            $user->nama_lengkap,
            $donasi->formatted_jumlah,
            $donasi->tanggal_donasi->locale('id')->translatedFormat('d F Y'),
            $donasi->metode_pembayaran,
            $donasi->tujuan_donasi,
            $donasi->kegiatan?->judul,
            $donasi->id
        ));

        Log::info('Email donasi terverifikasi dikirim ke: ' . $user->email);

        return true;
    }

    /**
     * Get template pesan notifikasi
     */
    private function getTemplate($tipe)
    {
        $templates = [
            'ucapan_terima_kasih_donasi' => '
                <h2>Terima Kasih atas Donasi Anda</h2>
                <p>Yth. {nama},</p>
                <p>Terima kasih atas donasi Anda sebesar <strong>{jumlah}</strong> yang telah diterima pada {tanggal}.</p>
                <h3>Detail Donasi:</h3>
                <ul>
                    <li>ID Transaksi: {id_transaksi}</li>
                    <li>Jumlah: {jumlah}</li>
                    <li>Metode Pembayaran: {metode}</li>
                    <li>Tujuan: {tujuan}</li>
                    <li>Untuk Kegiatan: {kegiatan}</li>
                </ul>
                <p>Donasi Anda sangat berarti bagi kami dan akan digunakan untuk mendukung kegiatan sosial Karinakas.</p>
                <p>Salam kasih,<br>Tim Karinakas</p>
            ',
            'konfirmasi_relawan' => '...',
            'perubahan_status_donasi' => '...',
        ];

        return $templates[$tipe] ?? '';
    }

    /**
     * Log notifikasi ke database atau file
     */
    private function logNotifikasi($data)
    {
        // TODO: Save to notifikasi_log table
        Log::channel('notifikasi')->info('Notifikasi Log', $data);
    }

    /**
     * F84: Mengirim notifikasi email setelah registrasi berhasil
     */
    public function kirimNotifikasiRegistrasi($userId)
    {
        // TODO: Implement
    }

    /**
     * F85: Mengirim notifikasi email pendaftaran relawan
     */
    public function kirimNotifikasiRelawan($relawanId)
    {
        // TODO: Implement
    }

    /**
     * F87: Mengirim notifikasi pembatalan kegiatan
     */
    public function kirimNotifikasiPembatalanKegiatan($kegiatanId)
    {
        // TODO: Implement
    }

    /**
     * F88: Mengirim notifikasi ke admin saat ada pendaftaran baru
     */
    public function kirimNotifikasiKeAdmin($type, $data)
    {
        // TODO: Implement
    }
}