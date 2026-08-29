<?php

namespace App\Http\Controllers;

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
     * Kirim notifikasi donasi (email/WA) setelah pembayaran berhasil
     * Dipanggil otomatis dari DonasiController
     */
    public function kirimNotifikasiDonasi($donasiId, $channel = 'both')
    {
        $donasi = Donasi::with(['user', 'kegiatan'])->findOrFail($donasiId);

        // Skip jika notifikasi sudah terkirim
        if ($donasi->notifikasi_terkirim) {
            return;
        }

        $success = false;

        try {
            // Kirim Email
            if (in_array($channel, ['email', 'both'])) {
                $this->kirimEmail($donasi);
            }

            // Kirim WhatsApp
            if (in_array($channel, ['whatsapp', 'both'])) {
                $this->kirimWhatsApp($donasi);
            }

            // Update flag notifikasi_terkirim
            $donasi->update(['notifikasi_terkirim' => true]);

            $success = true;

            // Log notifikasi
            $this->logNotifikasi([
                'donasi_id' => $donasi->id,
                'channel' => $channel,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi donasi: ' . $e->getMessage());
            
            $this->logNotifikasi([
                'donasi_id' => $donasi->id,
                'channel' => $channel,
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
        $template = $this->getTemplate('ucapan_terima_kasih_donasi');

        $data = [
            'nama' => $donasi->user->nama_lengkap,
            'jumlah' => $donasi->formatted_jumlah,
            'tanggal' => $donasi->tanggal_donasi->format('d F Y'),
            'id_transaksi' => $donasi->id,
            'metode' => $donasi->metode_pembayaran,
            'tujuan' => $donasi->tujuan_donasi,
            'kegiatan' => $donasi->kegiatan ? $donasi->kegiatan->judul : 'Donasi Umum',
        ];

        // TODO: Implement actual email sending with Laravel Mail
        /*
        Mail::send('emails.donasi-terima-kasih', $data, function($message) use ($donasi) {
            $message->to($donasi->user->email, $donasi->user->nama_lengkap)
                    ->subject('Terima Kasih atas Donasi Anda - Karinakas');
        });
        */

        Log::info('Email terima kasih dikirim ke: ' . $donasi->user->email);
        
        return true;
    }

    /**
     * Kirim WhatsApp ucapan terima kasih
     */
    private function kirimWhatsApp($donasi)
    {
        $template = $this->getTemplate('ucapan_terima_kasih_donasi_wa');

        $message = str_replace([
            '{nama}',
            '{jumlah}',
            '{tanggal}',
            '{tujuan}',
        ], [
            $donasi->user->nama_lengkap,
            $donasi->formatted_jumlah,
            $donasi->tanggal_donasi->format('d F Y'),
            $donasi->tujuan_donasi,
        ], $template);

        // TODO: Implement WhatsApp Business API integration
        // Send via Twilio, MessageBird, or similar service

        Log::info('WhatsApp terima kasih dikirim ke: ' . $donasi->user->no_telepon);

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
            'ucapan_terima_kasih_donasi_wa' => "
Halo {nama},

Terima kasih atas donasi Anda! 🙏

Detail donasi:
💰 Jumlah: {jumlah}
📅 Tanggal: {tanggal}
🎯 Tujuan: {tujuan}

Donasi Anda sangat membantu kegiatan sosial kami.

Berkah Dalem,
Karinakas
            ",
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