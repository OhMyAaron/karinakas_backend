<?php

namespace App\Mail;

use App\Models\Relawan;
use App\Support\TemplateNotifikasi;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RelawanDiterima extends Mailable
{
    use Queueable, SerializesModels;

    public $namaUser;
    public $jenis;
    public $kegiatan;

    public function __construct($namaUser, $jenis = null, $kegiatan = null)
    {
        $this->namaUser = $namaUser;
        $this->jenis    = $jenis;
        $this->kegiatan = $kegiatan;
    }

    /** Susun surel dari satu pendaftaran relawan (relasi user & kegiatan dimuat bila belum). */
    public static function dari(Relawan $relawan): self
    {
        $relawan->loadMissing(['user', 'kegiatan']);

        return new self(
            $relawan->user?->nama_lengkap,
            $relawan->jenis,
            $relawan->kegiatan?->judul
        );
    }

    /** Judul & isi surel dari templat "relawan" yang disunting admin. */
    public function tersusun(): array
    {
        return TemplateNotifikasi::render('relawan', [
            'nama'     => $this->namaUser,
            'jenis'    => $this->jenis ?: '-',
            // Relawan umum tidak terikat kegiatan: hindari kalimat "untuk kegiatan -"
            'kegiatan' => $this->kegiatan ?: 'umum Karinakas',
        ]);
    }

    public function judulSurel(): string
    {
        return $this->tersusun()['judul'];
    }

    public function build()
    {
        $surel = $this->tersusun();

        return $this->subject($surel['judul'])
                    ->view('emails.relawan-diterima')
                    ->with(['judulSurel' => $surel['judul'], 'isiSurel' => $surel['isi']]);
    }
}
